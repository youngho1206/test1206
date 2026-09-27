// 공통 유틸 함수
function escapeHtml(str){
  return String(str).replace(/[&<>"']/g, function(c){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
  });
}

async function fetchJSON(url){
  const res = await fetch(url, {cache:"no-store"});
  if(!res.ok) throw new Error("요청 실패: " + res.status);
  return await res.json();
}

function extOf(name){
  const i = name.lastIndexOf(".");
  return i === -1 ? "" : name.slice(i+1).toLowerCase();
}

function timeAgo(ts){
  const d = new Date(ts);
  return d.getFullYear()+"."+String(d.getMonth()+1).padStart(2,"0")+"."+String(d.getDate()).padStart(2,"0")+
         " "+String(d.getHours()).padStart(2,"0")+":"+String(d.getMinutes()).padStart(2,"0");
}

// 페이지를 벗어날 때(닫기/새로고침/다른 페이지 이동) 이 페이지의 캐시를 자동으로 정리합니다.
function clearPageCache(){
  try{ sessionStorage.clear(); }catch(e){}
  try{ localStorage.clear(); }catch(e){}
  if (window.caches && caches.keys){
    caches.keys().then(function(keys){
      keys.forEach(function(k){ caches.delete(k); });
    }).catch(function(){});
  }
}
window.addEventListener("pagehide", clearPageCache);
window.addEventListener("beforeunload", clearPageCache);
