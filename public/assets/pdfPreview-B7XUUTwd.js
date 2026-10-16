import{p as r}from"./index-Bin80VaY.js";function p(t){if(t instanceof Blob)return t.type&&t.type!=="application/pdf"?new Blob([t],{type:"application/pdf"}):t;const e=(t==null?void 0:t.data)??t;return e instanceof Blob?e.type&&e.type!=="application/pdf"?new Blob([e],{type:"application/pdf"}):e:new Blob([e],{type:"application/pdf"})}async function c(t,e){if((await t.slice(0,5).text()).startsWith("%PDF"))return t.type==="application/pdf"?t:new Blob([t],{type:"application/pdf"});try{const n=JSON.parse(await t.text());throw new Error((n==null?void 0:n.message)||e)}catch(n){throw n instanceof Error&&n.message&&n.message!==e&&!(n instanceof SyntaxError)&&!/JSON/i.test(n.message)?n:new Error(e)}}async function d(t,e="Gagal membuka PDF."){var i;if(t instanceof Blob)return c(p(t),e);const o=(t==null?void 0:t.status)??0,n=String(((i=t==null?void 0:t.headers)==null?void 0:i["content-type"])||""),a=t==null?void 0:t.data;if(o&&o!==200)throw new Error(await r({response:t},e));if(n.includes("application/json")||n.includes("text/html"))throw new Error(await r({response:t},e));return c(p(a??t),e)}async function l(t,e="document.pdf"){const o=await d(t,"File PDF tidak valid atau gagal diunduh."),n=window.URL.createObjectURL(o),a=window.open(n,"_blank","noopener,noreferrer");if(a)try{a.document.title=e}catch{}return setTimeout(()=>window.URL.revokeObjectURL(n),6e4),!!a}async function w(t,e="Preview PDF",o="Gagal membuka PDF."){const n=await d(t,o);return s(n,e)}function s(t,e="Preview PDF"){const o=t instanceof Blob?t.type==="application/pdf"?t:new Blob([t],{type:"application/pdf"}):new Blob([t],{type:"application/pdf"}),n=URL.createObjectURL(o),a=window.open("","_blank");if(!a)return URL.revokeObjectURL(n),!1;const i=String(e).replace(/[<>&"]/g,"");return a.document.write(`<!DOCTYPE html><html><head><title>${i}</title>
    <style>
      * { box-sizing: border-box; }
      body { margin: 0; font-family: system-ui, sans-serif; background: #0f172a; }
      .toolbar {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        padding: 10px 14px; background: #0f172a; color: #f8fafc;
        border-bottom: 1px solid #1e293b; position: sticky; top: 0; z-index: 2;
      }
      .toolbar h1 { margin: 0; font-size: 14px; font-weight: 600; }
      .toolbar .hint { font-size: 12px; color: #94a3b8; margin-left: 8px; font-weight: 400; }
      .actions { display: flex; gap: 8px; }
      .actions button {
        border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600;
        cursor: pointer; font-size: 13px;
      }
      .btn-print { background: #059669; color: #fff; }
      .btn-close { background: #334155; color: #e2e8f0; }
      iframe { width: 100%; height: calc(100vh - 52px); border: 0; background: #525659; }
    </style></head><body>
    <div class="toolbar">
      <h1>${i}<span class="hint">Preview — cetak dari tombol di bawah atau dari viewer PDF</span></h1>
      <div class="actions">
        <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
        <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
      </div>
    </div>
    <iframe id="pdfFrame" src="${n}" title="Preview PDF"></iframe>
  </body></html>`),a.document.close(),setTimeout(()=>URL.revokeObjectURL(n),12e4),!0}export{w as a,s as b,d as e,l as o};
