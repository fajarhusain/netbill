import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch
import numpy as np
import os

os.chdir("/Users/fajarhusainasyari/netbill/manuscript/images")

c = ['#6366f1', '#1e1b4b', '#22c55e', '#ef4444', '#f59e0b', '#64748b']
cats = ['Auth','Access','Scan','Payment','Cancel','Invoice','QR','Collection','Report','Dash','Dusun','Cust-D','Del/Imp']
t = [8,3,7,9,5,3,5,6,9,2,8,6,12]
a = [20,6,14,31,15,31,10,13,22,5,19,14,33]

fig, ax = plt.subplots(figsize=(8,4))
bars = ax.barh(cats, t, color=[c[0] if v>=8 else c[5] for v in t], height=0.7)
ax.bar_label(bars, padding=3)
ax.set_xlabel('Test Cases')
ax.set_title('Fig 1. Test Cases by Category (93 Total)')
ax.set_xlim(0, 16)
plt.tight_layout()
plt.savefig('p1_fig1.png', dpi=200)
plt.close()

fig, ax = plt.subplots(figsize=(8,4))
y = np.arange(len(cats))
ax.barh(y-0.18, t, height=0.35, color=c[0], label='Tests')
ax.barh(y+0.18, a, height=0.35, color=c[5], label='Assertions')
ax.set_yticks(y)
ax.set_yticklabels(cats, fontsize=8)
ax.set_xlabel('Count')
ax.set_title('Fig 2. Tests vs Assertions by Category')
ax.legend()
plt.tight_layout()
plt.savefig('p1_fig2.png', dpi=200)
plt.close()

fig, ax = plt.subplots(figsize=(8,3))
times = [0.8,0.6,1.0,1.2,0.8,1.5,1.1,1.3,1.5,0.5,1.5,1.0,1.2]
ax.bar(cats, times, color=c[0], width=0.6)
ax.set_ylabel('Time (s)')
ax.set_title('Fig 3. Execution Time by Category')
plt.xticks(rotation=45, ha='right', fontsize=8)
plt.tight_layout()
plt.savefig('p1_fig3.png', dpi=200)
plt.close()
print("Paper 1 images done")