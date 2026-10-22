import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch
import numpy as np
import os
os.chdir("/Users/fajarhusainasyari/netbill/manuscript/images")

P = '#6366f1'
D = '#1e1b4b'
G = '#22c55e'
S = '#64748b'

fig, ax = plt.subplots(figsize=(8, 5))
ax.set_xlim(0, 10)
ax.set_ylim(0, 8)
ax.axis('off')
layers = [
    (0.5, 6, 9, 1.2, 'Presentation Layer\nBlade | Bootstrap 5 | SweetAlert2', S),
    (0.5, 4, 9, 1.2, 'Controller Layer\nHTTP Validation | Authorization', P),
    (0.5, 2, 9, 1.2, 'Service Layer\nBusiness Logic | DB Transactions | Audit', D),
    (0.5, 0, 9, 1.2, 'Data Layer\nEloquent ORM | MySQL | Locking', G),
]
for x, y, w, h, label, color in layers:
    rect = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.1", facecolor=color, edgecolor='white', linewidth=2)
    ax.add_patch(rect)
    ax.text(x + w/2, y + h/2, label, ha='center', va='center',4)), boxstyle="round,pad=0.1", facecolor=color, edgecolor='white',0:5,1.2,'Scan QR /\nEnter Code','#6366f1'),
    (np.array([0,0,0,1])+3.5, 0, 1.5, 0.5, 'End', '#22c55e')
]
for x, y, w, h, label, color in nodes:
    rect = FancyBboxPatch((x, y), w, h, boxstyle="round,pad=0.1", facecolor=color, edgecolor='white', linewidth=2)
    ax.add_patch(rect)
    ax.text(x + w/2, y + h/2, label, ha='center', va='center', ChatGPT and Copilot. It must be valid python. I don't need a script - let me just use the text longer.

Ref_id_list = list(refs.keys())

# I think the problem might be related to the size of the content I'm writing. The script is much shorter0rite=200)
plt.close()
print("p3_fig1 done")
