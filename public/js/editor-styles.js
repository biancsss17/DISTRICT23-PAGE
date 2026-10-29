export function buildEditorStyles(styles, config) {
    let result = '';
    Object.entries(config.targets).forEach(([key, [label, selector]]) => {
        const s = styles[key] || {},
            rules = [];
        if (config.fonts[s.font]) rules.push(`font-family:${config.fonts[s.font]}!important`);
        if (s.size) {
            const n = Math.min(120, Math.max(10, Number(s.size)));
            rules.push(
                `font-size:${n > 32 ? `clamp(28px,${n / 14.4}vw,${n}px)` : `${n}px`}!important`,
            );
        }
        if (s.weight) rules.push(`font-weight:${Number(s.weight)}!important`);
        if (['left', 'center', 'right'].includes(s.align))
            rules.push(`text-align:${s.align}!important`);
        if (/^#[a-f0-9]{6}$/i.test(s.color || '')) rules.push(`color:${s.color}!important`);
        if (rules.length) result += `${selector}{${rules.join(';')}}`;
    });
    const b = styles.background || {};
    if (
        ['solid', 'gradient'].includes(b.mode) &&
        /^#[a-f0-9]{6}$/i.test(b.start || '') &&
        /^#[a-f0-9]{6}$/i.test(b.end || '')
    ) {
        const value =
            b.mode === 'solid'
                ? b.start
                : `linear-gradient(${Number(b.angle) || 0}deg,${b.start},${b.end})`;
        result += `.home-page{background:${value}!important}.home-page>.site-ribbons{display:none!important}.home-page main>.section,.home-page main>.stats-section{background:transparent!important}`;
    }
    if (styles.spacing)
        result += `.home-page main .section{padding-top:${Number(styles.spacing)}px;padding-bottom:${Number(styles.spacing)}px}`;
    return result;
}
