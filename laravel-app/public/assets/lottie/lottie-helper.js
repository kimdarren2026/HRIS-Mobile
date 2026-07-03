// Lightweight wrapper around the locally-vendored lottie-web player.
// Usage: mountLottie('gps-loading-anim', '/assets/lottie/gps-loading.json', { loop: true });
(function () {
    function toMs(value) {
        var ms = Number(value);
        return Number.isFinite(ms) && ms > 0 ? ms : null;
    }

    window.mountLottie = function (containerId, jsonPath, opts) {
        var el = document.getElementById(containerId);
        if (!el || typeof window.lottie === 'undefined' || typeof window.lottie.loadAnimation !== 'function') {
            return null;
        }

        opts = opts || {};

        try {
            var animation = window.lottie.loadAnimation({
                container: el,
                renderer: 'svg',
                loop: opts.loop !== undefined ? opts.loop : false,
                autoplay: opts.autoplay !== undefined ? opts.autoplay : true,
                path: jsonPath,
            });

            var pauseAfterMs = toMs(opts.pauseAfterMs);
            var stopAfterMs = toMs(opts.stopAfterMs);
            var hideAfterMs = toMs(opts.hideAfterMs);
            var durationMs = toMs(opts.durationMs);

            if (durationMs && !pauseAfterMs && !stopAfterMs && !hideAfterMs) {
                pauseAfterMs = durationMs;
            }

            if (pauseAfterMs) {
                window.setTimeout(function () {
                    if (animation && typeof animation.pause === 'function') animation.pause();
                }, pauseAfterMs);
            }

            if (stopAfterMs) {
                window.setTimeout(function () {
                    if (animation && typeof animation.stop === 'function') animation.stop();
                }, stopAfterMs);
            }

            if (hideAfterMs) {
                window.setTimeout(function () {
                    if (animation && typeof animation.pause === 'function') animation.pause();
                    el.style.transition = el.style.transition || 'opacity 180ms ease';
                    el.style.opacity = '0';
                    window.setTimeout(function () {
                        el.style.display = 'none';
                    }, 200);
                }, hideAfterMs);
            }

            return animation;
        } catch (e) {
            return null;
        }
    };
})();
