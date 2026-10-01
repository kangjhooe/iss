import{r as m,l as x,o as ee,D as i,B as o,G as s,H as C,I as r,P,$ as R,K as N,E as D,Q as z,a0 as te,x as M}from"./vue-Du24L0lx.js";import{_ as ae,u as se,a as le,H as y}from"./index-DJ0_76y_.js";import{o as ne}from"./pdfPreview-Ctfj0ab6.js";import{s as ie}from"./student-DG9KIvL2.js";import{e as oe}from"./teacher-C9RGZLGu.js";import{c as de}from"./class-BjiZZfNd.js";import"./pinia-DcNlwu5q.js";import"./vue-router-BFLIbfh7.js";import"./axios-C0Zqfgkc.js";const re={class:"qr-generate-page"},ue={class:"page-header"},ce={class:"header-content"},pe={class:"header-actions"},ve=["disabled"],me=["disabled"],ge={class:"toolbar"},fe=["value"],he=["disabled"],be=["disabled"],ke=["disabled"],ye={key:0,class:"hint-text"},_e={key:1,class:"hint-text"},we={key:2,class:"hint-text"},xe={key:3,class:"table-container"},Ce={class:"data-table"},Pe={class:"col-check"},Se=["checked"],$e=["value"],qe={key:4,class:"cards-section"},Ae={class:"section-title"},Ge={class:"card-grid"},Ne={class:"qr-card-head"},Be={class:"qr-card-body"},Ie={class:"qr-frame"},Qe=["src","alt"],De={class:"qr-meta"},Re={key:0},ze={key:1},Me={key:2},Ee={key:3},Ke=["onClick"],Fe={__name:"QrCodeGenerate",setup(Te){const g=le(),S=se(),n=m("student"),u=m(""),h=m(""),$=m([]),_=m([]),B=m([]),l=m([]),p=m([]),b=m(!1),w=m(!1),q=m(!1),A=m(!1),I=x(()=>{var a;return((a=$.value.find(t=>String(t.id)===String(u.value)))==null?void 0:a.name)||""}),E=x(()=>n.value==="employee"||!!u.value),K=x(()=>n.value==="employee"||!!u.value||l.value.length),G=x(()=>{const a=h.value.trim().toLowerCase(),t=n.value==="student"?_.value:B.value;return a?t.filter(e=>`${e.name||""} ${e.nis||""} ${e.nisn||""} ${e.nip||""} ${e.type||""}`.toLowerCase().includes(a)):t}),F=x(()=>{const a=G.value.map(t=>t.id);return a.length>0&&a.every(t=>l.value.includes(t))});function T(a){const t=G.value.map(e=>e.id);a.target.checked?l.value=[...new Set([...l.value,...t])]:l.value=l.value.filter(e=>!t.includes(e))}function V(){u.value="",h.value="",_.value=[],l.value=[],p.value=[]}function U(){h.value="",l.value=[],p.value=[],L()}async function j(){try{const a=await de.getAll({per_page:200,status:"Aktif"});$.value=a.data.data||[]}catch{$.value=[]}}async function L(){var a;if(_.value=[],!!u.value){w.value=!0;try{const t=[];let e=1,d=1;do{const c=await ie.getAll({class_id:u.value,status:"Aktif",per_page:100,page:e,sort_by:"name",sort_dir:"asc"});t.push(...c.data.data||[]),d=Number(((a=c.data.meta)==null?void 0:a.last_page)||1),e+=1}while(e<=d&&e<=20);_.value=t}catch{_.value=[],g.error("Gagal memuat daftar siswa","Daftar siswa tidak dapat dimuat. Periksa koneksi dan coba lagi.")}finally{w.value=!1}}}async function J(){var a;w.value=!0;try{const t=[];let e=1,d=1;do{const c=await oe.getAll({per_page:100,page:e,status:"Aktif"});t.push(...c.data.data||[]),d=Number(((a=c.data.meta)==null?void 0:a.last_page)||1),e+=1}while(e<=d&&e<=20);B.value=t.sort((c,f)=>String(c.name||"").localeCompare(String(f.name||""),"id"))}catch{B.value=[],g.error("Gagal memuat daftar pegawai","Daftar pegawai tidak dapat dimuat. Periksa koneksi dan coba lagi.")}finally{w.value=!1}}async function H(){if(!(n.value==="student"&&!u.value)){b.value=!0;try{const a=n.value==="student"?await y.generateStudentBulk({class_id:Number(u.value)}):await y.generateEmployeeBulk({});if(p.value=a.data.data.cards||[],!p.value.length){g.warning("Kosong",a.data.message||"Tidak ada data aktif.");return}g.success("Berhasil",`${p.value.length} kartu QR siap dicetak.`)}catch(a){g.error("Gagal generate",a.formattedMessage||"QR tidak dapat digenerate.")}finally{b.value=!1}}}async function O(){if(l.value.length){b.value=!0;try{const a=n.value==="student"?await y.generateStudentBulk({class_id:u.value?Number(u.value):void 0,student_ids:l.value}):await y.generateEmployeeBulk({employee_ids:l.value});p.value=a.data.data.cards||[],g.success("Berhasil",`${p.value.length} kartu QR siap dicetak.`)}catch(a){g.error("Gagal generate",a.formattedMessage||"QR tidak dapat digenerate.")}finally{b.value=!1}}}function Q(a){return String(a||"").toLowerCase().replace(/[^a-z0-9]+/gi,"-").replace(/^-|-$/g,"")||"qr"}function k(a){return String(a??"").replace(/[&<>"']/g,t=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[t])}function Y(a){const t=document.createElement("a");t.href=a.qr_code;const e=a.nis||a.nip||a.id,d=a.class_name||n.value;t.download=`qr-${Q(d)}-${Q(e)}-${Q(a.name)}.svg`,document.body.appendChild(t),t.click(),document.body.removeChild(t)}function W(a){const t=[];for(let e=0;e<a.length;e+=2)t.push(`
      <tr>
        <td>${a[e]||""}</td>
        <td>${a[e+1]||""}</td>
      </tr>
    `);return t.join("")}function X(){var d,c,f;if(!p.value.length)return;q.value=!0;const a=((d=S.activeInstitution)==null?void 0:d.name)||((f=(c=S.user)==null?void 0:c.institution)==null?void 0:f.name)||"Sekolah",t=p.value.map(v=>`
    <div class="card">
      <div class="card-head">${k(a)}</div>
      <table class="card-body">
        <tr>
          <td class="qr-cell">
            <div class="qr-frame">
              <img src="${v.qr_code}" alt="" />
            </div>
            <div class="scan-hint">Scan saat absensi</div>
          </td>
          <td class="meta-cell">
            <div class="primary-id">${k(v.name)}</div>
            <table class="meta-rows">
              ${v.nis?`<tr><td class="lbl">NIS</td><td class="val">${k(v.nis)}</td></tr>`:""}
              ${v.nip?`<tr><td class="lbl">NIP</td><td class="val">${k(v.nip)}</td></tr>`:""}
              ${v.class_name?`<tr><td class="lbl">Kelas</td><td class="val">${k(v.class_name)}</td></tr>`:""}
              ${v.type?`<tr><td class="lbl">Jenis</td><td class="val">${k(v.type)}</td></tr>`:""}
            </table>
          </td>
        </tr>
      </table>
      <div class="card-foot">Kartu QR Absensi</div>
    </div>
  `),e=window.open("","_blank");if(!e){q.value=!1,g.error("Gagal","Pop-up diblokir. Izinkan tab baru untuk mencetak.");return}e.document.write(`<!DOCTYPE html>
    <html lang="id">
      <head>
        <title>Kartu QR Absensi</title>
        <style>
          @page { size: A4 portrait; margin: 8mm; }
          * { box-sizing: border-box; margin: 0; padding: 0; }
          body { font-family: Arial, Helvetica, sans-serif; color: #0f172a; line-height: 1.35; }
          .page-title { font-size: 11pt; font-weight: 700; color: #065f46; margin: 0 0 6mm; }
          .grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
          .grid td { width: 50%; padding: 3mm; vertical-align: top; }
          .card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            height: 63mm;
            background: #fff;
            break-inside: avoid;
            page-break-inside: avoid;
          }
          .card-head {
            background: #047857;
            color: #fff;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            padding: 3px 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .card-body { width: 100%; border-collapse: collapse; }
          .qr-cell { width: 36mm; text-align: center; vertical-align: middle; padding: 5px 4px 4px 6px; }
          .qr-frame {
            display: inline-block;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 3px;
          }
          .qr-frame img { width: 30mm; height: 30mm; display: block; }
          .scan-hint { font-size: 7pt; color: #64748b; margin-top: 2px; }
          .meta-cell { vertical-align: middle; padding: 6px 8px 6px 2px; }
          .primary-id {
            font-size: 13pt;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 5px;
            line-height: 1.15;
            word-break: break-word;
          }
          .meta-rows { width: 100%; border-collapse: collapse; }
          .meta-rows td { padding: 1px 0; vertical-align: top; font-size: 9pt; }
          .meta-rows .lbl { width: 14mm; color: #64748b; padding-right: 3px; white-space: nowrap; }
          .meta-rows .val { color: #1e293b; font-weight: 700; word-break: break-word; }
          .card-foot {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 7pt;
            padding: 2px 8px;
            text-align: right;
            letter-spacing: 0.3px;
            text-transform: uppercase;
          }
        </style>
      </head>
      <body>
        <h1 class="page-title">Kartu QR Absensi${I.value?" — "+k(I.value):""}</h1>
        <table class="grid">${W(t)}</table>
      </body>
    </html>`),e.document.close(),e.focus(),e.print(),q.value=!1}async function Z(){var a,t;A.value=!0;try{const e=n.value==="student"?await y.printStudentPdf({class_id:u.value||void 0,student_ids:l.value.length?l.value.join(","):void 0}):await y.printEmployeePdf({employee_ids:l.value.length?l.value.join(","):void 0});if((((a=e.headers)==null?void 0:a["content-type"])||"").includes("application/json")){const f=typeof((t=e.data)==null?void 0:t.text)=="function"?await e.data.text():String(e.data),v=(()=>{try{return JSON.parse(f)}catch{return{}}})();throw new Error(v.message||"Gagal mencetak PDF.")}const c=e.data instanceof Blob?e.data:new Blob([e.data],{type:"application/pdf"});ne(c,`qr-absensi-${n.value}.pdf`)||g.error("Gagal","Pop-up diblokir. Izinkan tab baru untuk melihat PDF.")}catch(e){g.error("Gagal PDF",e.formattedMessage||e.message||"PDF tidak dapat dibuat.")}finally{A.value=!1}}return ee(async()=>{await Promise.all([j(),J()])}),(a,t)=>(o(),i("div",re,[s("div",ue,[s("div",ce,[t[5]||(t[5]=s("div",null,[s("h1",{class:"page-title"},"Generate QR Absensi"),s("p",{class:"page-subtitle"},"Kartu QR tetap untuk siswa atau pegawai. Cetak per kelas, lalu scan saat absensi.")],-1)),s("div",pe,[s("button",{type:"button",class:"btn-secondary btn-compact",disabled:!p.value.length||q.value,onClick:X}," Cetak kartu ",8,ve),s("button",{type:"button",class:"btn-secondary btn-compact",disabled:!K.value||A.value,onClick:Z},r(A.value?"Menyiapkan PDF...":"Cetak PDF"),9,me)])])]),s("div",ge,[P(s("select",{"onUpdate:modelValue":t[0]||(t[0]=e=>n.value=e),class:"form-select",onChange:V},[...t[6]||(t[6]=[s("option",{value:"student"},"Siswa",-1),s("option",{value:"employee"},"Guru/Staff",-1)])],544),[[R,n.value]]),n.value==="student"?(o(),i(N,{key:0},[P(s("select",{"onUpdate:modelValue":t[1]||(t[1]=e=>u.value=e),class:"form-select",onChange:U},[t[7]||(t[7]=s("option",{value:""},"Pilih kelas",-1)),(o(!0),i(N,null,D($.value,e=>(o(),i("option",{key:e.id,value:e.id},r(e.name),9,fe))),128))],544),[[R,u.value]]),P(s("input",{"onUpdate:modelValue":t[2]||(t[2]=e=>h.value=e),type:"search",class:"form-select",placeholder:"Cari nama / NIS",disabled:!u.value},null,8,he),[[z,h.value]])],64)):P((o(),i("input",{key:1,"onUpdate:modelValue":t[3]||(t[3]=e=>h.value=e),type:"search",class:"form-select",placeholder:"Cari nama / NIP"},null,512)),[[z,h.value]]),s("button",{type:"button",class:"btn-primary btn-compact",disabled:!E.value||b.value,onClick:H},r(b.value?"Menggenerate...":n.value==="student"?"Generate semua di kelas":"Generate semua pegawai"),9,be),s("button",{type:"button",class:"btn-secondary btn-compact",disabled:!l.value.length||b.value,onClick:O}," Generate terpilih ("+r(l.value.length)+") ",9,ke)]),n.value==="student"&&!u.value?(o(),i("p",ye,"Pilih kelas untuk melihat siswa dan generate massal.")):w.value?(o(),i("p",_e,"Memuat daftar...")):G.value.length?(o(),i("div",xe,[s("table",Ce,[s("thead",null,[s("tr",null,[s("th",Pe,[s("input",{type:"checkbox",checked:F.value,onChange:T},null,40,Se)]),s("th",null,r(n.value==="student"?"NIS":"NIP"),1),t[8]||(t[8]=s("th",null,"Nama",-1)),s("th",null,r(n.value==="student"?"Kelas":"Jenis"),1)])]),s("tbody",null,[(o(!0),i(N,null,D(G.value,e=>(o(),i("tr",{key:e.id},[s("td",null,[P(s("input",{type:"checkbox",value:e.id,"onUpdate:modelValue":t[4]||(t[4]=d=>l.value=d)},null,8,$e),[[te,l.value]])]),s("td",null,r(n.value==="student"?e.nis||"—":e.nip||"—"),1),s("td",null,r(e.name),1),s("td",null,r(n.value==="student"?I.value||"—":e.type||"—"),1)]))),128))])])])):(o(),i("p",we,"Tidak ada data aktif yang sesuai.")),p.value.length?(o(),i("div",qe,[s("h2",Ae,r(p.value.length)+" kartu siap cetak",1),s("div",Ge,[(o(!0),i(N,null,D(p.value,e=>{var d,c,f;return o(),i("article",{key:e.id,class:"qr-card"},[s("div",Ne,r(((d=M(S).activeInstitution)==null?void 0:d.name)||((f=(c=M(S).user)==null?void 0:c.institution)==null?void 0:f.name)||"Sekolah"),1),s("div",Be,[s("div",Ie,[s("img",{src:e.qr_code,alt:`QR ${e.name}`,class:"qr-image"},null,8,Qe)]),s("div",De,[s("strong",null,r(e.name),1),e.nis?(o(),i("span",Re,"NIS "+r(e.nis),1)):C("",!0),e.nip?(o(),i("span",ze,"NIP "+r(e.nip),1)):C("",!0),e.class_name?(o(),i("span",Me,r(e.class_name),1)):C("",!0),e.type?(o(),i("span",Ee,r(e.type),1)):C("",!0)])]),t[9]||(t[9]=s("div",{class:"qr-card-foot"},"Kartu QR Absensi",-1)),s("button",{type:"button",class:"btn-link qr-download",onClick:v=>Y(e)},"Unduh",8,Ke)])}),128))])])):C("",!0)]))}},Xe=ae(Fe,[["__scopeId","data-v-c0917d06"]]);export{Xe as default};
