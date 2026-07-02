// Lightweight wrapper around the locally-vendored lottie-web player.
// Usage: mountLottie('gps-loading-anim', '/assets/lottie/gps-loading.json', { loop: true });
window.mountLottie = function (containerId, jsonPath, opts) {
    var el = document.getElementById(containerId);
    if (!el || typeof lottie === 'undefined') return null;
    opts = opts || {};
    return lottie.loadAnimation({
        container: el,
        renderer: 'svg',
        loop: opts.loop !== undefined ? opts.loop : false,
        autoplay: opts.autoplay !== undefined ? opts.autoplay : true,
        path: jsonPath,
    });
};
