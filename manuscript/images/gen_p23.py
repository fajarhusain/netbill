import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Ellipse
import numpy as np
import os
os.chdir("/Users/fajarhusainasyari/netbill/manuscript/images")

P, D, G, S = '#6366f1', '#1e1b4b', '#22c55e', '#64748b'

# P3 Fig1: Layered architecture
fig, ax = plt.subplots(figsize=(8, 5))
ax.set_xlim(0, 10)
ax.set_ylim(0, 8)
ax.axis('off')
layers = [
    (0.5, 6, 9, 1.2, 'Presentation Layer\nBlade | Bootstrap 5 | SweetAlert2 | html5-qrcode', S),
    (0.5, 4, 9, 1.2, 'Controller Layer\nHTTP Validation | Authorization | Middleware', P),
    (0.5, 2, 9, 1.2, 'Service Layer\nBusiness Logic | DB Transactions | Audit Logging', D),
    (0.5, 0, 9, 1.2, 'Data Layer\nEloquent ORM | MySQL | Pessimistic Locking', G),
]
for x, y, w, h, label, color in layers:
    rect = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.1", facecolor=color, edgecolor='white', linewidth=2)
    ax.add_patch(rect)
    ax.text(x + w/2, y + h/2, label, ha='center', va='center', fontweight='bold', color='white')
for i in range(len(layers)-1):
    ax.annotate('', xy=(5, layers[i+1][1]), xytext=(5, layers[i][1]),
                arrowprops=dict(arrowstyle='->', color=D, lw=2))
ax.set_title('Fig 1. Layered Architecture of NetBill', fontweight='bold')
plt.tight_layout()
plt.savefig('p3_fig1.png', dpi=200)
plt.close()

# P3 Fig2: Payment allocation flowchart
fig, ax = plt.subplots(figsize=(7, 6))
ax.set_xlim(0, 8)
ax.set_ylim(0, 10)
ax.axis('off')
nodes = [
    (4, 9.5, 'Start', G),
    (4, 8, 'Receive Payment\n(with idempotency key)', P),
    (4, 6.5, 'Existing key found?', D),
    (4, 5, 'Lock invoices\n(SELECT FOR UPDATE)', P),
    (4, 3.5, 'Validate amount\n(0 < amount <= balance)', D),
    (4, 2, 'Allocate FIFO\n(DB::transaction)', P),
    (4, 0.5, 'Audit Log\nEnd', G),
]
for x, y, label, color in nodes:
    rect = FancyBboxPatch((x-2, y-0.4), 4, 0.8, boxstyle="round,pad=0.1", facecolor=color, edgecolor='white', linewidth=2)
    ax.add_patch(rect)
    ax.text(x, y, label, ha='center', fontsize=7, fontweight='bold', color='white')
for i in range(len(nodes)-1):
    ax.annotate('', xy=(4, nodes[i+1][1]+0.4), xytext=(4, nodes[i][1]-0.4),
                arrowprops=dict(arrowstyle='->', color=D, lw=2))
ax.set_title('Fig 2. Payment Allocation Flowchart', fontweight='bold')
plt.tight_layout()
plt.savefig('p3_fig2.png', dpi=200)
plt.close()

# P3 Fig3: Critical tests chart
fig, ax = plt.subplots(figsize=(8, 4))
cats = ['Payment', 'Cancel', 'Invoice', 'Del/Imp', 'Access']
vals = [9, 5, 3, 12, 3]
bars = ax.bar(cats, vals, color=[P, D, P, P, S, 0,0ems done")

# P2 Fig1: Use case diagram
fig, ax = plt.subplots(figsize=(8, 5))
ax.set_xlim(0, 12)
ax.set_ylim(0, 8)
ax.axis('off')
boundary = FancyBboxPatch((2.5, 0.5), 6, 7, boxstyle="round,pad=0.1",
    facecolor='#f8fafc', edgecolor=D, linewidth=2, linestyle='--')
ax.add_patch(boundary)
ax.text(5.5, 7.8, 'NetBill System', ha='center', 3.0)
ax.set_title('Fig 2. Scan-to-Pay Workflow', fontweight='bold')
plt.tight_layout()
plt.savefig('p2_fig2.png', dpi=200)
plt.close()
print("p2 images done")
