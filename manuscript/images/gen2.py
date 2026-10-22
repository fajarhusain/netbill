import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import numpy as np

cats = ['Navigation', 'Layout', 'Data Display', 'Touch Target', 'Actions']
x = np.arange(len(cats))
w = 0.35
fig, ax = plt.subplots(figsize=(8, 4))
ax.bar(x - w/2, [5, 2, 3, 5, 4], w, color='#6366f1', label='Mobile')
ax.bar(x + w/2, [5, 5, 5, 3, 5], w, color='#64748b', label='Desktop')
ax.set_ylabel('UX Score')
ax.set_title('Fig 3. UX Score by Device')
ax.set_xticks(x)
ax.set_xticklabels(cats, fontsize=8)
ax.legend()
ax.set_ylim(0, 6)
plt.tight_layout()
plt.savefig('p2_fig3.png', dpi=200)
plt.close()
print("p2_fig3 done")
