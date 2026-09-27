/*!
 * coi.js — Cross-Origin Isolation helper
 * 세가 새턴(야바우제) 코어의 멀티스레드 가속은 SharedArrayBuffer(스레드)가 필요하고,
 * SharedArrayBuffer는 COOP/COEP 응답 헤더가 있어야만 사용할 수 있습니다.
 * 정적 호스팅(GitHub Pages 등)처럼 헤더를 직접 넣을 수 없는 환경에서는
 * 이 서비스워커가 응답에 헤더를 붙여 대신 cross-origin isolation을 만들어 줍니다.
 *
 * 조건: https 또는 localhost 에서만 동작합니다 (file:// 불가).
 * Safari/iOS는 COEP credentialless를 지원하지 않아 동작하지 않을 수 있습니다.
 */
if (typeof window === "undefined") {
  // ---- 서비스워커 컨텍스트 ----
  self.addEventListener("install", () => self.skipWaiting());
  self.addEventListener("activate", (e) => e.waitUntil(self.clients.claim()));

  self.addEventListener("message", (ev) => {
    if (ev.data && ev.data.type === "deregister") {
      self.registration.unregister().then(() =>
        self.clients.matchAll().then((cs) => cs.forEach((c) => c.navigate(c.url)))
      );
    }
  });

  self.addEventListener("fetch", (event) => {
    const req = event.request;
    if (req.cache === "only-if-cached" && req.mode !== "same-origin") return;

    event.respondWith(
      fetch(req)
        .then((res) => {
          if (res.status === 0) return res;
          const headers = new Headers(res.headers);
          headers.set("Cross-Origin-Embedder-Policy", "credentialless");
          headers.set("Cross-Origin-Opener-Policy", "same-origin");
          if (!headers.has("Cross-Origin-Resource-Policy")) {
            headers.set("Cross-Origin-Resource-Policy", "cross-origin");
          }
          return new Response(res.body, {
            status: res.status,
            statusText: res.statusText,
            headers: headers,
          });
        })
        .catch((e) => {
          console.error("[coi.js]", e);
          throw e;
        })
    );
  });
} else {
  // ---- 페이지 컨텍스트 ----
  (function () {
    if (window.crossOriginIsolated) return; // 이미 격리됨 (서버가 헤더를 보냄)
    if (!window.isSecureContext) {
      console.warn("[coi.js] https 또는 localhost 에서만 동작합니다.");
      return;
    }
    if (!("serviceWorker" in navigator)) return;

    var me = document.currentScript && document.currentScript.src;
    if (!me) return;

    navigator.serviceWorker
      .register(me, { scope: "./" })
      .then(function (reg) {
        reg.addEventListener("updatefound", function () {
          window.location.reload();
        });
        // 등록은 됐지만 아직 이 페이지를 제어하지 않으면 한 번 새로고침해야 적용됨
        if (reg.active && !navigator.serviceWorker.controller) {
          window.location.reload();
        }
      })
      .catch(function (e) {
        console.error("[coi.js] 서비스워커 등록 실패", e);
      });
  })();
}
