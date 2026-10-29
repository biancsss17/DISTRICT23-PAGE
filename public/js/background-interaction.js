(() => {
    'use strict';
    const layers = [...document.querySelectorAll('.hero-ribbons')];
    if (!layers.length) return;
    const allowed = matchMedia(
        '(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)',
    );
    const lights = layers.map((layer) => {
        const light = document.createElement('span');
        light.className = 'pointer-light';
        layer.append(light);
        return light;
    });
    let targetX = 0,
        targetY = 0,
        x = 0,
        y = 0,
        frame = 0;
    function draw() {
        frame = 0;
        x += (targetX - x) * 0.085;
        y += (targetY - y) * 0.085;
        document.body.style.setProperty('--pointer-drift-x', `${(x * 65).toFixed(2)}px`);
        document.body.style.setProperty('--pointer-drift-y', `${(y * 30).toFixed(2)}px`);
        lights.forEach((light) => {
            const rect = light.parentElement.getBoundingClientRect();
            light.style.transform = `translate3d(${((x + 1) * innerWidth) / 2 - rect.left}px, ${((y + 1) * innerHeight) / 2 - rect.top}px, 0)`;
        });
        if (Math.abs(targetX - x) + Math.abs(targetY - y) > 0.001)
            frame = requestAnimationFrame(draw);
    }
    function schedule() {
        if (!frame && !document.hidden) frame = requestAnimationFrame(draw);
    }
    function reset() {
        targetX = targetY = 0;
        document.body.classList.remove('background-following');
        schedule();
    }
    document.addEventListener(
        'pointermove',
        (event) => {
            if (!allowed.matches || event.pointerType === 'touch') return;
            targetX = Math.max(-1, Math.min(1, (event.clientX / innerWidth) * 2 - 1));
            targetY = Math.max(-1, Math.min(1, (event.clientY / innerHeight) * 2 - 1));
            document.body.classList.add('background-following');
            schedule();
        },
        { passive: true },
    );
    document.documentElement.addEventListener('pointerleave', reset);
    window.addEventListener('blur', reset);
    window.addEventListener(
        'scroll',
        () => {
            if (allowed.matches) schedule();
        },
        { passive: true },
    );
    window.addEventListener('resize', reset);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            cancelAnimationFrame(frame);
            frame = 0;
        } else reset();
    });
    allowed.addEventListener('change', () => {
        reset();
        if (!allowed.matches) {
            cancelAnimationFrame(frame);
            frame = 0;
            x = y = 0;
            document.body.style.removeProperty('--pointer-drift-x');
            document.body.style.removeProperty('--pointer-drift-y');
        }
    });
})();
