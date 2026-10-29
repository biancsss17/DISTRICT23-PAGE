document.addEventListener('pointerdown', (event) => {
    document.querySelectorAll('.admin-guide[open]').forEach((guide) => {
        if (!guide.contains(event.target)) guide.open = false;
    });
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape')
        document.querySelectorAll('.admin-guide[open]').forEach((guide) => {
            guide.open = false;
            guide.querySelector('summary')?.focus();
        });
});
