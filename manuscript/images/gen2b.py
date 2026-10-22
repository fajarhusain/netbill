import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch
import numpy as np

# P2 Fig1: Use case diagram
fig, ax = plt.subplots(figsize=(8, 5))
ax.set_xlim(0, 12)
ax.set_ylim(0, 8)
ax.axis('off')

boundary = FancyBboxPatch((2.5, 0.5), 6, 7, boxstyle="round,pad=0.1",
    facecolor='#f8fafc', edgecolor='#1e1b4b', linewidth=2, linestyle='--')
ax.add_patch(boundary)
ax.text(5.5, 7.8, 'NetBill System', ha='center', fontweight='bold')

ax.text(0.5, 5.5, 'Collector', fontweight='bold')
ax.plot([1.2, 2.5], [5.5, 4.0], 'k-', lw=1.5)
ax.text(0.5, 1.5, 'Admin', fontweight='bold')
ax.plot([1.2, 2.5], [1.5, 2.0], 'k-', lw=0.5)

ucs = [
    (5.5, 6.5, 'Scan QR Code'),
    (5.5, 5.3, 'Process Payment'),
    (5.5, 4.1, 'View Arrears'),
    (5.5, 2.9, 'Record Visit'),
    (8.0, 5.3, 'Manage Customers'),
    (8.0, 4.1, 'Generate Invoices'),
    (8.0, 2.9, 'View Reports'),
]
for cx, cy, label in ucs:
    el = pltEllipse((cx, cy), 2.2, 0.6, facecolor='#6366f1', edgecolor='white', linewidth=1.5, alpha=0.85)
    ax.add_patch(el)
    ax.text(cx, cy, label, ha='center', va='center', fontsize=7, color='white', fontweight='bold')

ax.set_title('Fig 1. Use Case Diagram',5)
ax.set_ylim(0,10)
ax.axis('off')

steps = ['Scan QR /\nEnter Code', 'View Customer\n& Arrears', 'Select Invoices\n& Enter Amount', 'Confirm\nPayment']
colors = ['#6366f1', '#1e1b4b', '#22c55e', '#6366f1']
for i, (label, color) in enumerate(zip(steps, colors)):
    rect = FancyBboxPatch((1 + i*2.8, 3.5), 2.0, 1.5, boxstyle="round,pad=0.1", facecolor=color, edgecolor='white', linewidth=2)
    ax.add_patch(rect)
    ax.text(2 + i*2.8, 4.25, label, ha='center', va='center', fontsize=7,on. Transactional integrity is maintained through database-level constraints and audit logging. Transactions are wrapped in DB::transaction() calls within service methods. When a payment is processed, all invoice updates occur within a single transaction, ensuring that a failure at any point rolls back all changes.

SweetAlert2 is used for all confirmation dialogs and flash messages, providing a consistent interaction pattern across role-specific interfaces. The PWA manifest enables installation to the home screen. SweetAlert2 is used for all confirmation dialogs and flash messages. When a comment is updated or executive-check the test suite validates this. The test suite confirms transactional integrity across scenarios. A limitation is the absence of load testing. Future work should add browser-based E2E testing.

Conclusion

This study presented an automated functional testing strategy for a billing system with 93 tests. The results demonstrate effective mitigation of financial risks.

Acknowledgments

The authors acknowledge PATITECH.ID for infrastructure support.

AI Usage Statement

In preparing this manuscript, the author used an AI assistant for grammar correction. The author reviewed and edited all content and assumes full responsibility.

Credit Authorship Contribution Statement

F.H.S.: Conceptualization, Methodology, Software, Testing, Writing.

Declaration Of Competing Interests

The authors declare no competing interests.

Data Availability

Source code available upon reasonable request.

References
