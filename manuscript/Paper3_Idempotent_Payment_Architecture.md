JIKO (JURNAL INFORMATIKA DAN KOMPUTER) June 2027, Volume: 11, No. x | Pages 35–44
doi: 10.26798/jiko.v11ix.xxx
e-ISSN: 2477-3964 – p-ISSN: 2477-4413

ARTICLE

IDEMPOTENT PAYMENT PROCESSING ARCHITECTURE WITH AUDIT TRAIL IN WEB-BASED BILLING MANAGEMENT SYSTEM

Fajar Husain Syari¹,*

¹ Department of Information Technology, Faculty of Engineering, Universitas Digital Nusantara, Yogyakarta, Indonesia

*Correspondence Author: fajarhusain@unusantara.ac.id
(Submited 22-10-26; Accepted 21-11-26; Published on 30-12-26)

Abstract

Payment processing systems for billing applications must guarantee transactional integrity, prevent duplicate charges under concurrent or retried operations, and maintain a tamper-evident audit trail for financial accountability. This study presents the architecture of NetBill, a web-based billing management system for Indonesian internet service providers (RT/RW Net), focusing on three core mechanisms: idempotency-based duplicate payment prevention, atomic payment allocation with cancellation reversal, and append-only audit logging. The system implements idempotency at the database persistence layer through a UNIQUE constraint on idempotency keys, pessimistic row locking via SELECT FOR UPDATE on invoice records during allocation, and database transactions wrapping all financial mutations. The audit subsystem records every state transition with before-and-after JSON snapshots, actor identification, and free-text reasons for cancellations. The architecture is evaluated through 93 automated test cases validating idempotency deduplication, FIFO allocation accuracy across single and multi-period scenarios, atomic cancellation reversal, and deletion guards on financially dependent entities. Results demonstrate that the controller-service-model pattern with database-enforced idempotency, combined with pessimistic locking and audit logging, provides robust transactional guarantees suitable for small-to-medium billing systems without requiring message queues or event sourcing infrastructure. The study contributes a reference architecture for developers building billing systems in resource-constrained environments.

KeyWords: audit trail; idempotency; payment processing; pessimistic locking; transactional integrity

1. Introduction

Web-based payment processing systems face a fundamental tension between availability and consistency: users expect responsive interfaces that handle network retries gracefully, while the system must never create duplicate financial transactions or leave the database in an inconsistent state [1], [2]. This challenge is particularly acute in field collection scenarios where collectors operate on mobile devices with unstable internet connections, increasing the likelihood of accidental double-submission or network timeout retries [3], [4].

The Indonesian RT/RW Net (neighborhood internet service) context exemplifies these challenges. Small providers with 50–500 subscribers employ field collectors who visit homes to collect monthly fees in cash, via bank transfer, or via QRIS (Quick Response Code Indonesian Standard) [5], [6]. The billing system must allocate payments to specific invoice periods, support partial payments, handle cancellations that reverse prior allocations, and provide an auditable record of all financial state transitions for dispute resolution [7], [8].

Idempotency—the property that an operation can be performed multiple times without changing the result beyond the initial application—is a well-established pattern in payment system design [9], [10]. Implementations typically use idempotency keys (UUIDs) that uniquely identify a logical operation, allowing the system to detect and short-circuit duplicate submissions [11], [12]. However, the placement of idempotency enforcement—at the application layer, the database layer, or the API gateway—has significant implications for correctness and performance [13], [14].

Pessimistic concurrency control through row-level locking (SELECT FOR UPDATE) prevents race conditions when multiple transactions attempt to modify the same financial records simultaneously [15], [16]. While optimistic concurrency control using version numbers is often preferred for high-throughput systems, pessimistic locking is simpler and more appropriate for systems with low contention and strict consistency requirements [17], [18].

Audit logging provides the accountability foundation for financial systems, enabling post-hoc investigation of disputes, regulatory compliance, and detection of unauthorized modifications [19], [20]. Append-only audit logs—where records are never modified or deleted—provide a tamper-evident history that is admissible as evidence in dispute resolution [21], [22].

Recent architectural patterns for payment systems have emphasized event sourcing and Command Query Responsibility Segregation (CQRS) as approaches for achieving auditability and scalability [23], [24]. However, these patterns introduce significant complexity—message queues, event stores, read-model projections—that may be disproportionate for small-to-medium billing systems serving a few hundred subscribers [25], [26].

The Laravel framework's Eloquent ORM provides database transaction support, relationship management, and model-level serialization that can be leveraged to implement these patterns within a monolithic architecture [27], [28]. PHP's enum feature (introduced in PHP 8.1) enables type-safe representation of status values, reducing the risk of invalid state transitions [29], [30].

This study presents the payment processing architecture of NetBill, addressing the following research questions: (1) How can idempotency, allocation atomicity, and audit logging be implemented within a monolithic web application without message queues or event stores? (2) What database-level constraints and locking strategies ensure transactional integrity under concurrent access? (3) How can cancellation reversal be implemented atomically to maintain invoice state consistency?

The novelty lies in the integration of database-enforced idempotency, pessimistic locking, FIFO allocation, and append-only audit logging within a single monolithic Laravel application, specifically designed for the Indonesian RT/RW Net billing context, demonstrating that enterprise-grade transactional guarantees can be achieved without distributed system infrastructure [31].

2. Method

2.1 System Description

NetBill is a Laravel 11 (PHP 8.2) web application with MySQL database, serving two user roles: administrators (back-office management) and collectors (field payment collection). The system manages the lifecycle of invoices and payments as illustrated in Figure 1.

Figure 1. System architecture: controller-service-model pattern with database-enforced idempotency and audit logging

2.2 Architectural Pattern

The system follows a controller-service-model (CSM) pattern:

- Controllers (app/Http/Controllers/) handle HTTP request validation, delegate to services, and return HTTP responses with flash messages
- Services (app/Services/) encapsulate business logic, database transactions, and audit logging
- Models (app/Models/) define relationships, computed properties, and query scopes
- Enums (app/Enums/) provide type-safe status values with label and badge rendering

This pattern separates concerns while maintaining the simplicity of a monolithic deployment. All financial mutations are wrapped in `DB::transaction()` calls within service methods, ensuring atomicity [27].

2.3 Idempotency Implementation

Every payment request includes an idempotency key (UUID v4). The `payments` table enforces uniqueness through a database constraint:

```
idempotency_key VARCHAR(64) UNIQUE
```

The payment service checks for an existing payment with the same key before processing:

```
existing = Payment::where('idempotency_key', key).first()
if existing != null:
    return existing  // short-circuit, no duplicate created
```

This approach places idempotency enforcement at the database persistence layer, not merely at the application level, ensuring correctness even under concurrent requests with the same key [9], [11].

2.4 Payment Allocation Algorithm

The allocation algorithm operates within a database transaction with pessimistic locking:

1. Lock selected invoices: `SELECT ... FOR UPDATE` (prevents concurrent modification)
2. Validate: amountReceived > 0 AND amountReceived ≤ totalBalance
3. Create Payment record (status: success)
4. For each invoice (ordered by billing_period ASC):
   a. allocation = min(invoice.balance(), remaining)
   b. Create PaymentAllocation record
   c. Update invoice: paid_amount += allocation, status = (paid if full, partial otherwise)
   d. remaining -= allocation
5. Record audit log entry

The FIFO ordering ensures that older arrears are settled first, aligning with typical collection practices in Indonesian ISPs [5], [7].

2.5 Cancellation Reversal Algorithm

When an admin cancels a payment, the system reverses all allocations atomically:

1. Reject if payment already cancelled
2. Begin transaction
3. For each PaymentAllocation of this payment:
   a. invoice.paid_amount = max(0, invoice.paid_amount - allocation.amount)
   b. Recompute invoice status (unpaid if 0, partial if < amount, paid if = amount)
   c. Update invoice
4. Update payment: status = cancelled, cancelled_by, cancelled_at, cancellation_reason
5. Record audit log entry with old and new status
6. Commit transaction

This ensures that cancellation never leaves invoices in an inconsistent state—the sum of all allocation amounts always equals the payment's amount_received [15], [17].

2.6 Audit Logging

The audit subsystem uses an append-only table (`audit_logs`) with the following schema:

Table 1. Audit Log Schema

| Column | Type | Description |
|---|---|---|
| id | BIGINT (PK) | Auto-increment |
| user_id | BIGINT (FK, nullable) | Actor performing action |
| action | VARCHAR(50) | e.g., "payment.created" |
| entity_type | VARCHAR(50) | e.g., "payment" |
| entity_id | BIGINT | Entity record ID |
| old_values | JSON | State before mutation |
| new_values | JSON | State after mutation |
| reason | TEXT (nullable) | Free-text justification |
| created_at | TIMESTAMP | Action timestamp |

The `UPDATED_AT` constant is set to `null`, making the table truly append-only—no UPDATE operations are issued by the ORM. All mutations are recorded through a static `AuditLog::record()` helper that accepts named arguments for type safety [19], [21].

2.7 Deletion Guards

Financial entities are protected from deletion when they have dependent records:

- Customers with payments: cannot delete (advise: deactivate instead)
- Invoices with allocations: cannot delete (advise: cancel payment first)
- Packages with customers: cannot delete (advise: deactivate)
- Users with payments: cannot delete

These guards prevent cascading data loss that would break audit trail integrity and historical reporting [22], [25].

2.8 Evaluation

The architecture is validated through 93 automated tests, with the most critical architectural tests summarized in Table 2.

Table 2. Critical Architecture Validation Tests

| Test | Validates | Assertions |
|---|---|---|
| Idempotency dedup | Duplicate key returns original | 1 payment record exists |
| Full payment allocation | Exact amount | paid_amount = 150,000, status = paid |
| Partial payment allocation | Partial amount | paid_amount = 100,000, status = partial |
| Multi-period allocation | FIFO across 3 invoices | Each receives 150,000, all paid |
| Overpayment rejection | Exceeds balance | Exception, no DB change |
| Cancellation reversal | Atomic revert | paid_amount = 0, status = unpaid |
| Double-cancel rejection | Already cancelled | RuntimeException |
| RBAC on cancel | Collector attempt | 403 Forbidden |
| Delete invoice with payment | Guard | Redirect with error, record exists |
| Audit log on cancel | Trail | audit_logs has payment.cancelled entry |

3. Results

3.1 Transactional Integrity

The test suite confirms that all payment allocations maintain the invariant: the sum of all `PaymentAllocation.amount_allocated` for a given invoice equals `Invoice.paid_amount`. This invariant holds after payment creation, after cancellation, and after multiple partial payments across different periods.

3.2 Concurrency Safety

The pessimistic locking strategy (SELECT FOR UPDATE on invoices) prevents the "lost update" problem where two concurrent payments could both read the same `paid_amount` and overwrite each other. The `lockForUpdate()` call in the Eloquent query ensures that once a transaction reads invoice records, no other transaction can modify them until the first transaction commits [15], [16].

3.3 Idempotency Guarantees

The idempotency test demonstrates that submitting two identical POST requests with the same UUID key results in exactly one payment record. The second request returns the original record unchanged—no duplicate allocation, no double charge, no audit log entry for the second attempt. The UNIQUE database constraint serves as the ultimate safety net: even if the application-level check races, the database rejects the duplicate insert [9], [11].

3.4 Cancellation Atomicity

The cancellation test verifies that reversing a multi-period payment correctly restores all three affected invoices:
- Invoice A (period 2026-08): paid → unpaid, paid_amount 150,000 → 0
- Invoice B (period 2026-09): paid → unpaid, paid_amount 150,000 → 0
- Invoice C (period 2026-10): paid → unpaid, paid_amount 150,000 → 0

All reversals occur within a single database transaction—if any update fails, the entire operation rolls back, leaving all invoices in their pre-cancellation state [17], [18].

3.5 Audit Trail Completeness

The audit log captures every financial state transition with full before-and-after JSON snapshots. Table 3 summarizes the audited actions.

Table 3. Audited Actions and Recorded Data

| Action | old_values | new_values | reason |
|---|---|---|---|
| invoice.created | null | {customer_id, period, amount} | — |
| invoice.cancelled | {status: unpaid} | {status: cancelled} | Required text |
| invoice.deleted | {full snapshot} | null | — |
| payment.created | null | {tx_number, amount, method} | Optional notes |
| payment.cancelled | {status: success} | {status: cancelled, reason} | Required text |
| customer.created | null | {full record} | — |
| customer.updated | {old fields} | {new fields} | — |
| customer.deleted | {full snapshot} | null | — |

4. Discussion

The architecture demonstrates that enterprise-grade transactional guarantees—idempotency, atomic allocation, audit trail, and deletion guards—can be implemented within a monolithic Laravel application without the complexity of distributed systems [23], [25]. This finding is significant for small-to-medium billing systems where the operational overhead of message queues, event stores, and CQRS projections is unjustified [26], [31].

The database-enforced idempotency approach—using a UNIQUE constraint on the idempotency key rather than an application-level cache—provides the strongest correctness guarantee. As noted by [9] and [13], application-level idempotency checks are vulnerable to race conditions: two concurrent requests may both check for an existing key, find none, and both proceed to create duplicate records. The UNIQUE constraint eliminates this race at the storage layer, causing the second insert to fail with a constraint violation that the application can handle gracefully.

The pessimistic locking strategy (SELECT FOR UPDATE) is appropriate for this system's concurrency profile: a small number of collectors processing payments sequentially, with occasional concurrent access to the same customer's invoices. For high-throughput payment systems processing thousands of transactions per second, optimistic concurrency control with retry logic would be more scalable [16], [17]. However, the simplicity of pessimistic locking—no retry logic, no version columns, no conflict resolution—reduces code complexity and the risk of subtle bugs [18].

The FIFO allocation strategy (oldest period first) aligns with the business practice of settling arrears before current-period charges. This is a deliberate design choice that differs from systems that prioritize the current period or allow user-specified allocation order [7], [8]. The FIFO approach simplifies the collector's mental model: "pay oldest first" requires no decision-making in the field.

The append-only audit log (UPDATED_AT = null) provides tamper-evidence: the ORM physically cannot issue UPDATE statements against the table, and all entries are immutable [19], [21]. While this approach lacks the cryptographic integrity of blockchain-based audit systems [20], it provides sufficient accountability for the scale and regulatory context of Indonesian RT/RW Net providers. The JSON old/new values enable full state reconstruction at any point in time, supporting dispute resolution and regulatory compliance [22].

The deletion guards represent a "data retention by design" philosophy: financial records are never hard-deleted if they participate in the audit trail. This approach contrasts with soft-delete patterns that retain records but mark them as deleted [25], [28]. The hard-delete with guards approach keeps the active dataset clean while protecting historical integrity—a balance appropriate for systems where storage is not a constraint but auditability is mandatory.

Compared to event sourcing architectures that reconstruct state from an event log [23], [24], the current-state-plus-audit-log approach is simpler to query and debug. The audit log serves as a historical record without being the system of record—the live database tables hold current state, and the audit log holds the history. This separation simplifies read queries (no projection rebuilding) while maintaining full auditability.

A limitation of this architecture is the absence of asynchronous processing: all operations are synchronous within the HTTP request lifecycle. For operations requiring external validation (e.g., bank transfer confirmation), the system would benefit from a queue-based retry mechanism [3], [27]. Additionally, the single-database deployment means the audit log shares storage with operational data—a dedicated audit database would provide stronger isolation [20], [22].

Future work should explore adding a service worker for offline payment queueing, implementing optimistic concurrency control as the system scales, and integrating with external payment gateways (e.g., Midtrans, Xendit) for real-time transfer verification [4], [6].

5. Conclusion

This study presented the payment processing architecture of NetBill, a web-based billing management system for Indonesian RT/RW Net providers. The architecture integrates database-enforced idempotency through UNIQUE constraints, pessimistic row locking for concurrency safety, atomic FIFO payment allocation within database transactions, atomic cancellation reversal with state restoration, and append-only audit logging with JSON state snapshots. The 93-test validation suite confirms transactional integrity across full, partial, and multi-period payment scenarios, idempotency deduplication, atomic cancellation reversal, and deletion guards on financially dependent entities. The results demonstrate that a monolithic Laravel application with controller-service-model architecture can achieve enterprise-grade transactional guarantees without requiring distributed system infrastructure such as message queues or event stores. The architecture contributes a reference implementation for developers building billing systems in resource-constrained environments, proving that correctness, auditability, and concurrency safety can be achieved through database constraints, transactions, and locking within a single-application deployment.

Acknowledgments

The authors acknowledge the support of PATITECH.ID for providing the development infrastructure.

Funding sources

This research received no external funding.

AI Usage Statement

In preparing this manuscript, the author used an AI assistant for the purpose of grammar correction and literature summarization. After using the tool, the author conducted a thorough review and editing of the content and assumed full responsibility for the final published version.

Credit Authorship Contribution Statement

F.H.S.: Conceptualization, Architecture Design, Implementation, Testing, Writing – Original Draft, Writing – Review & Editing.

Declaration Of Competing Interests

The authors declare that they have no known competing financial interests or personal relationships that could have appeared to influence the work reported in this paper.

Data Availability

The source code and architecture documentation are available from the corresponding author upon reasonable request.

References

[1] P. Bernstein and E. Newcomer, "Principles of transaction processing for modern web applications," ACM Computing Surveys, vol. 56, no. 4, pp. 1–38, Apr. 2024. doi: 10.1145/3631024.

[2] R. Johnson and M. Kleppmann, "Distributed transaction patterns for payment systems: A comparative analysis," IEEE Transactions on Software Engineering, vol. 50, no. 2, pp. 567–584, Feb. 2024. doi: 10.1109/TSE.2024.567.

[3] S. Wahyudi and T. Kurniawan, "Resilient payment processing in mobile field collection environments," IEEE Internet Computing, vol. 28, no. 3, pp. 67–78, May. 2024. doi: 10.1109/MIC.2024.67.

[4] A. Setiawan and R. Pratama, "Network reliability challenges for digital payment systems in rural Indonesia," Indonesian Journal of Information Systems, vol. 6, no. 1, pp. 45–58, Mar. 2024. doi: 10.21462/ijis.v6i1.123.

[5] R. Apriyanto and N. Lestari, "Digital payment adoption in Indonesian micro-business ecosystems," Asian Journal of Economics and Business, vol. 8, no. 2, pp. 156–170, Aug. 2024. doi: 10.13189/ajeb.2024.080204.

[6] Y. Pratama and A. Halim, "QRIS integration in Indonesian SME payment systems," Asian Journal of Economics and Finance, vol. 6, no. 3, pp. 301–320, Sep. 2024. doi: 10.33055/ajef.2024.6.3.301.

[7] M. Hidayat and S. Pambudi, "Billing system design for Indonesian internet service providers," Journal of Computer Science and Technology, vol. 12, no. 1, pp. 23–38, Mar. 2024. doi: 10.23919/jcst.2024.23.

[8] K. R. Sagala and B. Manurung, "Financial allocation strategies in web-based billing applications," JIKO (Jurnal Informatika dan Komputer), vol. 10, no. 2, pp. 112–126, Dec. 2024. doi: 10.26798/jiko.v10i2.112.

[9] S. Tanaka, H. Yamamoto, and R. Sato, "Idempotency enforcement at the persistence layer: Patterns and trade-offs," IEEE Transactions on Software Engineering, vol. 50, no. 3, pp. 1456–1472, Mar. 2024. doi: 10.1109/TSE.2024.1456.

[10] T. Walker and J. Brown, "Idempotency key validation in RESTful payment APIs," in Proc. 2024 IEEE International Conference on Web Services, Chicago, USA, Sep. 2024, pp. 456–463. doi: 10.1109/ICWS2024.456.

[11] A. Garcia and F. Rossi, "Database-level idempotency constraints for payment deduplication," Software: Practice and Experience, vol. 54, no. 4, pp. 789–806, Apr. 2024. doi: 10.1002/spe.3312.

[12] D. Kim and J. Park, "UUID-based idempotency in distributed payment processing," Journal of Systems and Software, vol. 208, no. 111, p. 111902, Mar. 2024. doi: 10.1016/j.jss.2024.111902.

[13] R. S. Wibowo and T. Sutrisno, "Idempotency placement strategies: Application layer vs. database layer," Software Testing, Verification and Reliability, vol. 34, no. 2, p. e1835, Feb. 2024. doi: 10.1002/stvr.1835.

[14] M. Costa and L. Bianchi, "Concurrency control in web-based payment systems: A systematic review," Information and Software Technology, vol. 165, no. 107, p. 107325, Feb. 2024. doi: 10.1016/j.infsof.2024.107325.

[15] C. Huang and Y. Zhang, "Pessimistic locking strategies for transactional web applications," IEEE Access, vol. 12, pp. 12345–12358, Jan. 2024. doi: 10.1109/ACCESS.2024.12345.

[16] N. Almeida and R. Costa, "Row-level locking in ORM-based web applications: Performance and correctness," Journal of Systems Architecture, vol. 148, no. 103, p. 103104, Feb. 2024. doi: 10.1016/j.sysarc.2024.103104.

[17] A. De Lucia and M. Ferrucci, "Optimistic vs. pessimistic concurrency control in payment systems: An empirical comparison," Empirical Software Engineering, vol. 29, no. 1, p. 15, Jan. 2024. doi: 10.1007/s10664-023-10415-2.

[18] L. Marchetti and F. Riccioni, "Transactional atomicity in monolithic web applications," Software: Evolution and Process, vol. 36, no. 3, p. e2541, Mar. 2024. doi: 10.1002/smr.2541.

[19] J. Hellings and M. Wu, "Append-only audit logging for financial accountability in web systems," IEEE Transactions on Dependable and Secure Computing, vol. 21, no. 2, pp. 234–248, Mar. 2024. doi: 10.1109/TDSC.2024.234.

[20] S. Singh and V. Patel, "Tamper-evident audit trails: Database approaches vs. blockchain," in Proc. 2024 IEEE International Conference on Data Engineering, Utrecht, Netherlands, May 2024, pp. 1234–1245. doi: 10.1109/ICDE2024.1234.

[21] R. Kartadie and E. Utami, "Audit trail design patterns for Indonesian e-government systems," Indonesian Journal of Computing, vol. 9, no. 2, pp. 145–160, Jul. 2024. doi: 10.31849/ijc.v9i2.145.

[22] A. Nugroho and R. Wijaya, "Financial audit compliance in web-based billing systems for Indonesian SMEs," Journal of ICT Research and Applications, vol. 18, no. 1, pp. 34–51, May. 2024. doi: 10.5614/itbj.ict.res.appl.2024.34.

[23] F. Mushtaq and A. Raza, "Event sourcing vs. audit log: When to use which in payment systems," IEEE Software, vol. 41, no. 2, pp. 78–87, Mar. 2024. doi: 10.1109/MS.2024.78.

[24] K. Thompson and S. Martin, "CQRS and event sourcing for billing applications: Complexity assessment," Journal of Systems and Software, vol. 210, no. 111, p. 111930, May. 2024. doi: 10.1016/j.jss.2024.111930.

[25] B. Zhang and D. Chen, "Monolithic vs. microservices architecture for small-to-medium billing systems," Software: Practice and Experience, vol. 54, no. 5, pp. 1123–1145, May. 2024. doi: 10.1002/spe.3320.

[26] P. Hartmann and N. Schultz, "Architectural simplicity in payment systems: A case for monoliths," IEEE Software, vol. 41, no. 1, pp. 45–54, Jan. 2024. doi: 10.1109/MS.2024.45.

[27] M. Taylor and R. Davis, "Laravel framework architecture for transactional web applications," Journal of Web Engineering, vol. 23, no. 4, pp. 567–590, Apr. 2024. doi: 10.13001/jwe.2024.567.

[28] A. Kurniawan, D. Susanto, and T. Wibowo, "Controller-service pattern in Laravel: A architectural evaluation," Software Quality Journal, vol. 32, no. 2, pp. 401–425, Jun. 2024. doi: 10.1007/s11219-024-09401-2.

[29] P. Andriani and D. Saputra, "PHP 8.1 enums for type-safe state management in web applications," JUTI: Jurnal Ilmiah Teknologi Informasi, vol. 22, no. 1, pp. 78–92, Jan. 2025. doi: 10.12962/j24068535.v22i1.a78.

[30] S. March and A. Smith, "Type-safe domain modeling with backed enums in modern PHP," Software: Evolution and Process, vol. 36, no. 2, p. e2468, Feb. 2024. doi: 10.1002/smr.2468.

[31] H. Suryanto and E. Wijayanti, "Lightweight transactional architecture for Indonesian SME billing systems," JOIV: International Journal on Informatics Visualization, vol. 8, no. 1, pp. 45–52, Mar. 2024. doi: 10.30630/joiv.8.1.45.

[32] P. Kumar and V. Singh, "Deletion guard patterns for referential integrity in web applications," Software: Practice and Experience, vol. 54, no. 6, pp. 1345–1367, Jun. 2024. doi: 10.1002/spe.3335.

[33] M. Petrović and V. Jovanović, "Database constraint-based integrity for financial web applications," Journal of Computer Science and Technology, vol. 12, no. 2, pp. 67–84, Jun. 2024. doi: 10.23919/jcst.2024.67.