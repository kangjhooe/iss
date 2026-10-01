import{l as y,r as R,o as Xe,w as se,D as l,B as o,G as a,H as f,Y as X,I as u,K as S,E as B,F as z,P as tt,$ as it,R as et,Z as ta,O as ne,J as ea,x as aa}from"./vue-Du24L0lx.js";import{b as sa,u as na}from"./vue-router-BFLIbfh7.js";import{C as ia,a as la,L as oa,b as ra,p as ua,e as da,f as ca,g as pa}from"./chart-DvVNqGk7.js";import{L as va}from"./LoadingSkeleton-C6AWvtAX.js";import{m as Lt,_ as ha,u as ma,h as ga,a as _a,v as ba,q as fa}from"./index-DJ0_76y_.js";import{i as ka}from"./institution-CkHL7_Pn.js";import{c as ya}from"./class-BjiZZfNd.js";import{s as $a}from"./semester-EO_ZeV0t.js";import{u as xa}from"./referenceData-9rSdsk4s.js";import{f as Sa}from"./printFooter-CWiAtxUP.js";import"./pinia-DcNlwu5q.js";import"./axios-C0Zqfgkc.js";import"./academicYear-Dg0Wut1c.js";const dt={getSummary(I){return Lt.get("/v1/bk-reports/summary",{params:I})},getViolationDetail(I){return Lt.get("/v1/bk-reports/violations",{params:I})},export(I){return Lt.get("/v1/bk-reports/export",{params:I,responseType:"blob"})},exportViolations(I){return Lt.get("/v1/bk-reports/export-violations",{params:I,responseType:"blob"})}},wa={class:"laporan-bk-page"},Ca={class:"toolbar"},Pa={class:"header-actions toolbar-actions"},Ta=["disabled"],La=["disabled"],Aa={key:0,class:"report-kind-nav","aria-label":"Jenis laporan"},Ba=["onClick"],Da={class:"tab-shell"},Na={class:"section-nav","aria-label":"Navigasi laporan BK"},Ra={class:"sec-text"},Ka={class:"sec-hint"},ja={class:"tab-main"},Ma={class:"tab-description"},Ha={key:0,class:"sub-nav",role:"tablist","aria-label":"Jenis catatan"},Va=["aria-selected"],Ja=["aria-selected"],za=["aria-selected"],Ea={class:"filters"},Ia=["value"],Fa=["value"],Oa=["disabled"],Ua={key:0,value:""},Ga=["value"],qa=["aria-expanded"],Ya={class:"filter-toggle-icon"},Za={class:"filters filters-advanced"},Wa=["value"],Qa=["value"],Xa={key:1,class:"filter-chips"},ts=["disabled","onClick"],es={key:0,"aria-hidden":"true"},as={key:2,class:"loading-wrap"},ss={class:"stat-cards"},ns={class:"summary-body"},is={class:"summary-value"},ls={class:"summary-label"},os={class:"summary-body"},rs={class:"summary-value"},us={class:"summary-label"},ds={class:"summary-body"},cs={class:"summary-value"},ps={class:"summary-body"},vs={class:"summary-value"},hs={class:"report-section"},ms={key:0,class:"empty-state"},gs={class:"empty-desc"},_s={key:1,class:"table-container"},bs={class:"data-table"},fs={key:0,class:"th-num"},ks={key:1,class:"th-num"},ys={key:2,class:"th-num"},$s=["onClick"],xs={key:0,class:"td-num"},Ss={key:1,class:"td-num td-good"},ws={key:2,class:"td-num"},Cs={class:"td-action"},Ps=["onClick"],Ts={class:"report-section"},Ls={class:"section-head"},As={class:"section-title"},Bs=["value"],Ds={key:0,class:"chart-box"},Ns={class:"chart-wrap"},Rs={class:"split-tables"},Ks={key:0,class:"report-section"},js={key:0,class:"table-container"},Ms={class:"data-table"},Hs={class:"td-num"},Vs={class:"td-num"},Js={key:1,class:"empty-desc muted"},zs={key:1,class:"report-section"},Es={key:0,class:"table-container"},Is={class:"data-table"},Fs={class:"td-num"},Os={class:"td-num td-good"},Us={key:1,class:"empty-desc muted"},Gs={key:0,class:"empty-state"},qs={key:1,class:"table-container"},Ys={class:"data-table"},Zs={class:"group-row"},Ws={colspan:"6"},Qs={class:"td-num"},Xs={class:"td-muted"},tn={class:"td-num td-good"},en={class:"td-muted"},an={key:0,class:"section-hint"},sn={key:1,class:"report-section"},nn={key:0,class:"empty-state"},ln={key:1,class:"table-container"},on={class:"data-table"},rn={class:"student-name"},un={class:"student-meta"},dn={class:"td-num"},cn={key:2,class:"report-section"},pn={key:0,class:"empty-state"},vn={class:"empty-title"},hn={class:"empty-desc"},mn={key:1,class:"table-container"},gn={class:"data-table"},_n={key:0},bn={class:"student-name"},fn={class:"student-meta"},kn={key:0},yn={class:"td-num td-good"},$n={key:3,class:"report-section"},xn={key:0,class:"empty-state"},Sn={key:1,class:"table-container"},wn={class:"data-table"},Cn={class:"student-name"},Pn={class:"student-meta"},Tn={key:6,class:"empty-state"},Ln={__name:"LaporanBk",setup(I){ia.register(la,oa,ra,ua,da,ca);const F=_a(),H=sa(),ie=na(),Ht=xa(),ct=ma(),le=y(()=>ga(ct.user,"violation")),oe=[{to:"/bk/laporan",label:"Gabungan",scope:"combined"},{to:"/bk/laporan/pelanggaran",label:"Pelanggaran",scope:"violations",requiresViolation:!0},{to:"/bk/laporan/prestasi",label:"Prestasi",scope:"achievements",purpose:"akreditasi",requiresViolation:!0},{to:"/bk/laporan/apresiasi",label:"Apresiasi",scope:"achievements",purpose:"apresiasi",requiresViolation:!0}],Vt=y(()=>oe.filter(e=>!e.requiresViolation||le.value)),pt=y(()=>H.meta.reportScope||"combined"),vt=y(()=>H.meta.reportPurpose||null),L=y(()=>pt.value==="combined"),k=y(()=>pt.value==="violations"),x=y(()=>pt.value==="achievements"),A=y(()=>vt.value==="apresiasi"),N=y(()=>vt.value==="akreditasi"),At=y(()=>!A.value);function re(e){return e.scope==="combined"?L.value:e.scope==="violations"?k.value:e.purpose==="apresiasi"?A.value:e.purpose==="akreditasi"?N.value:H.path===e.to}function ue(e){H.path!==e&&ie.push(e)}const Jt=y(()=>A.value?"Laporan Apresiasi":N.value?"Laporan Prestasi":k.value?"Laporan Pelanggaran":"Laporan BK"),zt=y(()=>A.value?"Laporan_Apresiasi":N.value?"Laporan_Prestasi":k.value?"Laporan_Pelanggaran":"Laporan_BK"),de={sekolah:"Sekolah",kabupaten:"Kabupaten/Kota",provinsi:"Provinsi",nasional:"Nasional",internasional:"Internasional"},ce={juara_1:"Juara 1",juara_2:"Juara 2",juara_3:"Juara 3",finalis:"Finalis",peserta:"Peserta",lainnya:"Lainnya"};function Bt(e){return de[e]||e||"—"}function Dt(e){return ce[e]||e||""}const pe={akademik:"Akademik",non_akademik:"Non Akademik",sikap:"Sikap"},Et={internasional:1,nasional:2,provinsi:3,kabupaten:4,sekolah:5};function ve(e){return pe[e]||e||"—"}function he(e){return[...e||[]].sort((t,i)=>{const r=(t.class_name||"").localeCompare(i.class_name||"");if(r!==0)return r;const c=Et[t.level]??6,v=Et[i.level]??6;return c!==v?c-v:(i.achievement_date||"").localeCompare(t.achievement_date||"")})}function me(e){return Yt(he(e))}function It(e){const t=e||[],i=new Set,r={},c={},v={};for(const d of t){d.student_id&&i.add(d.student_id);const $=d.level||"_kosong";r[$]=(r[$]||0)+1;const b=d.category||"_lainnya";c[b]=(c[b]||0)+1,d.rank&&(v[d.rank]=(v[d.rank]||0)+1)}return{total:t.length,students:i.size,byLevel:r,byCategory:c,byRank:v}}function ge(e){return!e||e==="_kosong"?"Belum diisi":Bt(e)}const O=R(!0),ht=R(!1),mt=R(!1),h=R("ringkasan"),P=R("violations"),E=R(!1),m=R(null),g=R(null),q=R({principal:{},bk:{}}),U=R([]),gt=R([]),at=R(null),Y=y(()=>{var e;return((e=ct.user)==null?void 0:e.bk_scope)==="homeroom"}),Ft=y(()=>{var e;return(((e=ct.user)==null?void 0:e.homeroom_class_ids)||[]).map(Number)}),G=y(()=>h.value!=="ringkasan"),_e=y(()=>Y.value?"Menampilkan siswa di kelas yang Anda walikan saja.":x.value?A.value?h.value==="ringkasan"?"Rekap apresiasi dan laporan positif siswa per kelas.":"Daftar apresiasi / laporan siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap prestasi lomba/kompetisi (akreditasi) per kelas, kategori, dan jenis.":"Daftar prestasi akreditasi sesuai filter yang dipilih.":k.value?h.value==="ringkasan"?"Rekap pelanggaran siswa per kelas dan jenis.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi.":"Daftar pelanggaran siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap kelas. Skor bersih = poin pelanggaran − poin prestasi.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.":"Catatan pelanggaran, prestasi, dan konseling sesuai filter yang dipilih."),_t=new Date().getFullYear(),Ot=[_t,_t-1,_t-2],p=R({academic_year_id:"",semester_id:"",class_id:"",month:"",year:String(_t)}),Nt=y(()=>Ht.academicYears||[]),Rt=y(()=>{const e=p.value.academic_year_id;return e?gt.value.filter(t=>String(t.academic_year_id)===String(e)):gt.value}),Ut=["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"],be={dicatat:"Dicatat",sanksi_diberikan:"Sanksi Diberikan",follow_up:"Follow Up",selesai:"Selesai"},fe={jadwal:"Jadwal",berlangsung:"Berlangsung",selesai:"Selesai",dibatalkan:"Dibatalkan"};function Z(e){return Ut[e]||`Bulan ${e}`}function Gt(e){return be[e]||e||"—"}function qt(e){return fe[e]||e||"—"}function ke(e){const t=Number(e);return Number.isNaN(t)?"":t>40?"score-bad":t>20?"score-warn":t<=0?"score-good":""}function W(e){if(!e)return"—";const[t,i,r]=e.split("-");return!t||!i||!r?e:`${r}/${i}/${t}`}function Yt(e){const t=[];let i=null,r=0;for(const c of e||[]){const v=c.class_name||"Tanpa kelas";(!i||i.name!==v)&&(i={key:v,name:v,rows:[],start:r},t.push(i)),i.rows.push(c),r+=1}return t}const Zt=y(()=>{var e;return Yt(((e=g.value)==null?void 0:e.by_student)||[])}),lt=y(()=>{const e=U.value.find(t=>String(t.id)===String(p.value.class_id));return(e==null?void 0:e.name)||""}),Wt=y(()=>{const e=[];return p.value.class_id&&lt.value&&e.push({key:"class",label:`Kelas ${lt.value}`,disabled:Y.value&&U.value.length<=1}),E.value&&p.value.month&&e.push({key:"month",label:`${Z(Number(p.value.month))} ${p.value.year}`,disabled:!1}),e}),ye=y(()=>{var r,c;const e=[];e.push(lt.value||"Semua kelas");const t=(r=Nt.value.find(v=>String(v.id)===String(p.value.academic_year_id)))==null?void 0:r.name;t&&e.push(t);const i=(c=Rt.value.find(v=>String(v.id)===String(p.value.semester_id)))==null?void 0:c.name;return i&&e.push(i),E.value&&p.value.month&&e.push(`${Z(Number(p.value.month))} ${p.value.year}`),e.join(" · ")}),Qt=y(()=>{const e=p.value.academic_year_id;return e&&Nt.value.find(t=>String(t.id)===String(e))||null}),$e=y(()=>{var e;return((e=Qt.value)==null?void 0:e.name)||"—"});function xe(e){if(e!=null&&e.start_date&&(e!=null&&e.end_date)){const v=new Date(e.start_date),d=new Date(e.end_date);return{startYear:v.getFullYear(),endYear:d.getFullYear()}}const t=String((e==null?void 0:e.name)||"").match(/(\d{4})\s*\/\s*(\d{4})/);if(t)return{startYear:parseInt(t[1],10),endYear:parseInt(t[2],10)};const i=new Date,r=i.getFullYear(),c=i.getMonth()+1>=7?r:r-1;return{startYear:c,endYear:c+1}}function Se(e,t){const{startYear:i,endYear:r}=xe(t),c={};for(const d of e||[]){if(!d.achievement_date)continue;const[$,b]=d.achievement_date.split("-").map(Number);if(!$||!b)continue;const D=`${$}-${b}`;c[D]=(c[D]||0)+1}const v=[];for(let d=0;d<12;d++){const $=(6+d)%12+1,b=$>=7?i:r;v.push({month:$,year:b,label:`${Z($)} ${b}`,count:c[`${b}-${$}`]||0})}return{slots:v,rangeLabel:`${Z(7)} ${i} – ${Z(6)} ${r}`}}function we(e){if(!(e!=null&&e.length))return'<tr><td colspan="9">Belum ada data prestasi untuk filter yang dipilih.</td></tr>';const t=me(e),i=9;return t.flatMap(r=>{const c=`<tr class="group-row"><td colspan="${i}">Kelas ${n(r.name)} · ${r.rows.length} prestasi</td></tr>`,v=r.rows.map((d,$)=>{const b=[d.nis,d.nisn].filter(Boolean).join(" / ")||"—",D=d.notes&&d.title&&d.notes!==d.title?d.notes:d.notes||"—";return`
      <tr>
        <td class="center">${r.start+$+1}</td>
        <td>
          <strong>${n(d.student_name||"—")}</strong><br>
          <span class="cell-sub">${n(b)}</span>
        </td>
        <td>${n(d.title||d.notes||"—")}</td>
        <td>${n(ve(d.category))}</td>
        <td>${n(d.achievement_type||"—")}</td>
        <td>${n(ge(d.level))}</td>
        <td class="center">${n(d.rank?Dt(d.rank):"—")}</td>
        <td class="center nowrap">${n(W(d.achievement_date))}</td>
        <td>${n(D)}</td>
      </tr>`}).join("");return c+v}).join("")}function Xt(e,t="Data Prestasi"){return`
    <h2>${n(t)}</h2>
    <table class="table-akreditasi">
      <thead>
        <tr>
          <th class="center col-no">No</th>
          <th class="col-nama">Nama Siswa</th>
          <th class="col-lomba">Lomba / Kegiatan</th>
          <th class="col-kat">Kategori</th>
          <th class="col-jenis">Jenis</th>
          <th class="col-tingkat">Tingkat</th>
          <th class="center col-rank">Peringkat</th>
          <th class="center col-tgl">Tanggal</th>
          <th class="col-ket">Keterangan</th>
        </tr>
      </thead>
      <tbody>${we(e)}</tbody>
    </table>
  `}function Ce(e,t="2. Tren Bulanan"){const{slots:i,rangeLabel:r}=Se(e,Qt.value),c=i.map(v=>`
    <tr>
      <td>${n(v.label)}</td>
      <td class="num">${v.count}</td>
    </tr>
  `).join("");return`
    <h2>${n(t)} <span class="h2-sub">(${n(r)})</span></h2>
    <table class="table-trend">
      <thead>
        <tr><th>Bulan</th><th class="num">Jumlah Prestasi</th></tr>
      </thead>
      <tbody>${c}</tbody>
    </table>
  `}function Pe(){var e;if(N.value&&h.value==="ringkasan"){const t=[],i=(e=Rt.value.find(r=>String(r.id)===String(p.value.semester_id)))==null?void 0:e.name;return i&&t.push(i),lt.value&&t.push(`Kelas ${lt.value}`),t.length?`<div class="period"><strong>Filter tambahan:</strong> ${n(t.join(" · "))}</div>`:""}return`<div class="period"><strong>Periode / Filter:</strong> ${n(ye.value)}</div>`}function Te(){return N.value&&h.value==="ringkasan"?`<div class="subtitle academic-year">Tahun Ajaran ${n($e.value)}</div>`:N.value?'<div class="subtitle">Prestasi Lomba & Kompetisi · Bimbingan Konseling</div>':`<div class="subtitle">${n(Jt.value)} · Bimbingan Konseling</div>`}function bt({forDetail:e=!1}={}){const t={academic_year_id:p.value.academic_year_id,semester_id:p.value.semester_id,class_id:p.value.class_id,year:p.value.year},i=E.value&&!!p.value.month;return e?i?t.month=p.value.month:delete t.year:i&&(t.month=p.value.month),Object.keys(t).forEach(r=>{r==="academic_year_id"||r==="semester_id"||(t[r]===""||t[r]===null||t[r]===void 0)&&delete t[r]}),vt.value&&(t.purpose=vt.value),t.report_scope=pt.value,t}const te=y(()=>{var i,r;const e=(r=(i=m.value)==null?void 0:i.by_month)==null?void 0:r.months;if(!(e!=null&&e.length))return null;const t=[];return x.value||t.push({label:"Pelanggaran",data:e.map(c=>c.violation_count),backgroundColor:"rgba(239, 68, 68, 0.7)",borderRadius:4}),k.value||t.push({label:"Prestasi",data:e.map(c=>c.achievement_count??0),backgroundColor:"rgba(16, 185, 129, 0.7)",borderRadius:4}),L.value&&t.push({label:"Konseling",data:e.map(c=>c.counseling_count),backgroundColor:"rgba(14, 165, 233, 0.7)",borderRadius:4}),t.length?{labels:e.map(c=>c.label||Ut[c.month]),datasets:t}:null}),Le={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom"}},scales:{y:{beginAtZero:!0,ticks:{precision:0}}}};function Kt(e){e!=null&&e.signers&&(q.value=e.signers)}async function st(){var e;O.value=!0;try{const t=await dt.getSummary(bt({forDetail:!1}));m.value=((e=t.data)==null?void 0:e.data)??null,Kt(m.value)}catch(t){m.value=null,F.error("Gagal memuat ringkasan",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}async function Ae(){var e;if(!(g.value&&Array.isArray(g.value.achievements)))try{const t=await dt.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Kt(g.value)}catch{}}async function Q(){var e;O.value=!0;try{const t=await dt.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Kt(g.value)}catch(t){g.value=null,F.error("Gagal memuat detail",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}function jt(){m.value=null,g.value=null}async function nt(){jt(),G.value?await Q():await st()}async function Be(){m.value=null,await st()}async function ot(e){h.value!==e&&(h.value=e,e==="ringkasan"?m.value||await st():g.value||await Q())}async function Mt(e){P.value=e,await ot("catatan")}async function ee(e){if(e.class_id&&(p.value.class_id=String(e.class_id)),m.value=null,g.value=null,x.value){P.value="achievements",h.value="catatan",await Q();return}h.value="skor",await Q()}async function De(e){if(e==="class"){if(Y.value&&U.value.length<=1)return;p.value.class_id=""}e==="month"&&(p.value.month=""),await nt()}async function Ne(){ht.value=!0;try{const e=G.value,t=bt({forDetail:e});e&&(t.export_view=h.value,L.value&&h.value==="catatan"&&(t.notes_kind=P.value));const i=e?await dt.exportViolations(t):await dt.export(t),r=window.URL.createObjectURL(new Blob([i.data])),c=document.createElement("a");c.href=r;const v=zt.value.replace(/_/g,"-").toLowerCase();c.setAttribute("download",e?`${v}-detail-${new Date().toISOString().slice(0,10)}.csv`:`${v}-per-kelas-${new Date().toISOString().slice(0,10)}.csv`),document.body.appendChild(c),c.click(),c.remove(),window.URL.revokeObjectURL(r),F.success("Export berhasil diunduh")}catch(e){F.error("Gagal mengekspor",e.formattedMessage||"Data tidak dapat diekspor.")}finally{ht.value=!1}}function n(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function Re(){if(!g.value)return"<p>Tidak ada data.</p>";const e=!k.value,t=e?9:7,i=(Zt.value||[]).flatMap(v=>{const d=`<tr class="group-row"><td colspan="${t}">Kelas ${n(v.name)} (${v.rows.length} siswa)</td></tr>`,$=v.rows.map((b,D)=>{const K=b.violation_points??b.total_points??0,j=b.achievement_points??0,V=b.score??K-j,J=e?`<td class="num">${n(b.achievement_count??0)}</td>
        <td class="num">−${n(j)}</td>`:"";return`
      <tr>
        <td>${v.start+D+1}</td>
        <td>${n(b.nis||"—")}</td>
        <td>${n(b.student_name||"—")}</td>
        <td>${n(b.class_name)}</td>
        <td class="num">${n(b.violation_count)}</td>
        <td class="num">${n(K)}</td>
        ${J}
        <td class="num"><strong>${n(V)}</strong></td>
      </tr>`}).join("");return d+$}).join("")||`<tr><td colspan="${t}">Belum ada data</td></tr>`;return`
    ${e?'<p class="note">Skor = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.</p>':'<p class="note">Rekap skor berdasarkan poin pelanggaran siswa.</p>'}
    <h2>Rekap Skor per Siswa</h2>
    <table>
      <thead>
        <tr>
          <th>#</th><th>NIS</th><th>Nama</th><th>Kelas</th>
          <th>Jml Pelanggaran</th><th>Poin Pelanggaran</th>
          ${e?"<th>Jml Prestasi</th><th>Poin Prestasi</th>":""}<th>Skor</th>
        </tr>
      </thead>
      <tbody>${i}</tbody>
    </table>
  `}function Ke(){var t;return`
    <h2>Daftar Pelanggaran</h2>
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
          <th>Jenis</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Pelapor</th>
        </tr>
      </thead>
      <tbody>${(((t=g.value)==null?void 0:t.items)||[]).map(i=>`
    <tr>
      <td>${n(W(i.violation_date))}</td>
      <td>${n(i.nis||"—")}</td>
      <td>${n(i.student_name||"—")}</td>
      <td>${n(i.class_name)}</td>
      <td>${n(i.violation_type)}</td>
      <td>${n(i.category)}</td>
      <td class="num">${n(i.point_weight)}</td>
      <td>${n(Gt(i.status))}</td>
      <td>${n(i.reporter_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="9">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function je(){var v;if(N.value)return Me();const e=At.value,t=A.value?"Uraian":"Lomba / Kegiatan",i=A.value?"Daftar Apresiasi":"Daftar Prestasi",r=e?"<th>Tingkat</th>":"",c=(((v=g.value)==null?void 0:v.achievements)||[]).map(d=>{const $=(d.purpose||"akreditasi")==="akreditasi",b=e?`<td>${$?`${n(Bt(d.level))}${d.rank?` · ${n(Dt(d.rank))}`:""}`:"—"}</td>`:"";return`
      <tr>
        <td>${n(W(d.achievement_date))}</td>
        <td>${n(d.nis||"—")}</td>
        <td>${n(d.student_name||"—")}</td>
        <td>${n(d.class_name)}</td>
        <td>${n(d.title||d.notes||"—")}</td>
        <td>${n(d.achievement_type)}</td>
        ${b}
        <td class="num">−${n(d.point_value)}</td>
        <td>${n(d.giver_name||"—")}</td>
      </tr>`}).join("")||`<tr><td colspan="${e?9:8}">Belum ada data</td></tr>`;return`
    <h2>${i}</h2>
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
          <th>${t}</th><th>Jenis</th>${r}<th>Poin</th><th>Pemberi</th>
        </tr>
      </thead>
      <tbody>${c}</tbody>
    </table>
  `}function Me(){var i;const e=((i=g.value)==null?void 0:i.achievements)||[];if(!e.length)return"<p>Belum ada data prestasi untuk filter yang dipilih.</p>";const t=It(e);return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${t.total}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${t.students}</div></div>
    </div>
    ${Xt(e,"Daftar Prestasi per Siswa")}
  `}function He(){var v,d;const e=((v=g.value)==null?void 0:v.achievements)||[],t=It(e),i=((d=m.value)==null?void 0:d.summary)||{},r=e.length?t.total:i.total_achievements??0,c=t.students;return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${r}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${c}</div></div>
    </div>
    ${Xt(e,"1. Data Prestasi")}
    ${Ce(e,"2. Tren Bulanan")}
  `}function Ve(){var t;return`
    <h2>Daftar Konseling</h2>
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
          <th>Jenis</th><th>Status</th><th>Konselor</th>
        </tr>
      </thead>
      <tbody>${(((t=g.value)==null?void 0:t.counseling)||[]).map(i=>`
    <tr>
      <td>${n(W(i.session_date))}</td>
      <td>${n(i.nis||"—")}</td>
      <td>${n(i.student_name||"—")}</td>
      <td>${n(i.class_name)}</td>
      <td>${n(i.counseling_type)}</td>
      <td>${n(qt(i.status))}</td>
      <td>${n(i.counselor_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="7">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function Je(){return k.value?"violations":x.value?"achievements":P.value}function ze(){if(!g.value)return"<p>Tidak ada data.</p>";const e=Je();return e==="violations"?Ke():e==="achievements"?je():Ve()}function Ee(){var b,D,K,j,V,J;if(N.value)return!m.value&&!((D=(b=g.value)==null?void 0:b.achievements)!=null&&D.length)?"<p>Tidak ada data.</p>":He();if(!m.value)return"<p>Tidak ada data.</p>";const e=m.value.summary||{},t=[];let i=1;const r=[];if(x.value||r.push(`<div class="stat"><div class="stat-label">Total Pelanggaran</div><div class="stat-value">${e.total_violations??0}</div></div>`),!k.value){const C=A.value?"Total Apresiasi":"Total Prestasi";r.push(`<div class="stat"><div class="stat-label">${C}</div><div class="stat-value">${e.total_achievements??0}</div></div>`)}L.value&&(r.push(`<div class="stat"><div class="stat-label">Skor Bersih</div><div class="stat-value">${e.net_score??0}</div></div>`),r.push(`<div class="stat"><div class="stat-label">Total Konseling</div><div class="stat-value">${e.total_counseling??0}</div></div>`)),r.length&&t.push(`<div class="stats">${r.join("")}</div>`),L.value&&t.push('<p class="note">Skor bersih = poin pelanggaran − poin prestasi.</p>');const c=["<th>Kelas</th>"];x.value||c.push("<th>Pelanggaran</th>"),k.value||c.push(`<th>${A.value?"Apresiasi":"Prestasi"}</th>`),L.value&&c.push("<th>Konseling</th>");const v=(m.value.by_class||[]).map(C=>{const w=[`<td>${n(C.class_name)}</td>`];return x.value||w.push(`<td class="num">${n(C.violation_count)}</td>`),k.value||w.push(`<td class="num">${n(C.achievement_count??0)}</td>`),L.value&&w.push(`<td class="num">${n(C.counseling_count)}</td>`),`<tr>${w.join("")}</tr>`}).join("")||`<tr><td colspan="${c.length}">Belum ada data</td></tr>`;t.push(`
    <h2>${i++}. Rekap per Kelas</h2>
    <table>
      <thead><tr>${c.join("")}</tr></thead>
      <tbody>${v}</tbody>
    </table>
  `);const d=["<th>Bulan</th>"];x.value||d.push("<th>Pelanggaran</th>"),k.value||d.push(`<th>${A.value?"Apresiasi":"Prestasi"}</th>`),L.value&&d.push("<th>Konseling</th>");const $=(((K=m.value.by_month)==null?void 0:K.months)||[]).map(C=>{const w=[`<td>${n(Z(C.month))}</td>`];return x.value||w.push(`<td class="num">${n(C.violation_count)}</td>`),k.value||w.push(`<td class="num">${n(C.achievement_count??0)}</td>`),L.value&&w.push(`<td class="num">${n(C.counseling_count)}</td>`),`<tr>${w.join("")}</tr>`}).join("")||`<tr><td colspan="${d.length}">Belum ada data</td></tr>`;if(t.push(`
    <h2>${i++}. Rekap per Bulan (${n(((j=m.value.by_month)==null?void 0:j.year)||p.value.year)})</h2>
    <table>
      <thead><tr>${d.join("")}</tr></thead>
      <tbody>${$}</tbody>
    </table>
  `),!x.value&&((V=m.value.top_violation_types)!=null&&V.length)){const C=m.value.top_violation_types.map((w,M)=>`
      <tr>
        <td>${M+1}</td>
        <td>${n(w.type_name)}</td>
        <td>${n(w.category)}</td>
        <td class="num">${n(w.count)}</td>
        <td class="num">${n(w.total_points)}</td>
      </tr>
    `).join("");t.push(`
      <h2>${i++}. Top Jenis Pelanggaran</h2>
      <table>
        <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
        <tbody>${C}</tbody>
      </table>
    `)}if(!k.value&&((J=m.value.top_achievement_types)!=null&&J.length)){const C=m.value.top_achievement_types.map((M,_)=>`
      <tr>
        <td>${_+1}</td>
        <td>${n(M.type_name)}</td>
        <td>${n(M.category||"—")}</td>
        <td class="num">${n(M.count)}</td>
        <td class="num">${n(M.total_points)}</td>
      </tr>
    `).join(""),w=A.value?"Top Jenis Apresiasi":"Top Jenis Prestasi";t.push(`
      <h2>${i++}. ${w}</h2>
      <table>
        <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
        <tbody>${C}</tbody>
      </table>
    `)}return t.join("")}function Ie(){return h.value==="ringkasan"?Ee():h.value==="skor"?Re():ze()}function Fe(){return N.value||G.value?"landscape":"portrait"}function Oe(){if(N.value){if(h.value==="ringkasan")return"Rekapitulasi Prestasi Siswa";if(h.value==="catatan")return"Daftar Prestasi Siswa — Lomba & Kompetisi"}const e=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor Siswa":"Catatan";return`${Jt.value} — ${e}`}async function Ue(){var t,i,r,c,v,d,$,b,D,K,j,V,J,C,w;if(!(G.value?!!g.value:!!m.value)&&!N.value){F.error("Gagal","Tidak ada data untuk dicetak");return}mt.value=!0;try{if(N.value&&await Ae(),!(N.value?!!(m.value||(i=(t=g.value)==null?void 0:t.achievements)!=null&&i.length):G.value?!!g.value:!!m.value)){F.error("Gagal","Tidak ada data untuk dicetak");return}const _=at.value||{},ft=_.name||"Sekolah",kt=[_.address,_.village?`Desa/Kel. ${_.village}`:"",_.sub_district?`Kec. ${_.sub_district}`:"",_.district,_.province,_.postal_code].filter(Boolean).join(", "),yt=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor_Siswa":"Catatan",$t=Oe(),xt=Fe(),St=Sa((r=ct.user)==null?void 0:r.name),wt=`${_.district||_.city||"........................"}, ${new Date().toLocaleDateString("id-ID",{day:"numeric",month:"long",year:"numeric"})}`,rt=`${zt.value}_${yt}_${new Date().toISOString().slice(0,10)}.pdf`,Ct=((v=(c=q.value)==null?void 0:c.principal)==null?void 0:v.role)||ba(_.level),Pt=(($=(d=q.value)==null?void 0:d.principal)==null?void 0:$.name)||_.principal_name||"",s=((D=(b=q.value)==null?void 0:b.principal)==null?void 0:D.nip)||_.principal_nip||"",T=((j=(K=q.value)==null?void 0:K.bk)==null?void 0:j.role)||"Guru Bimbingan Konseling",Tt=((J=(V=q.value)==null?void 0:V.bk)==null?void 0:J.name)||"",We=((w=(C=q.value)==null?void 0:C.bk)==null?void 0:w.nip)||"",Qe=`<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${n(rt)}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; margin: 16px; }
    h1 { font-size: 16px; margin: 0 0 4px; text-align: center; }
    .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
    .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
    .kop-logo { width: 66px; height: 66px; object-fit: contain; }
    .kop-text { min-width: 0; text-align: center; }
    .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
    .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
    .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
    .school-info { font-size: 9px; margin-top: 2px; }
    .subtitle { text-align: center; color: #444; margin-bottom: 12px; }
    .subtitle.academic-year { font-size: 13px; font-weight: 600; color: #111; margin-bottom: 14px; }
    .period { text-align: center; margin-bottom: 16px; font-size: 11px; }
    h2 { font-size: 12px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
    h2 .h2-sub { font-weight: 400; font-size: 11px; color: #555; text-transform: none; }
    table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 8px; }
    th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
    th { background: #eee; font-size: 10px; text-transform: uppercase; }
    tr.group-row td { background: #e2e8f0; font-weight: 700; text-transform: none; }
    td.num, th.num { text-align: right; }
    .stats { display: flex; gap: 8px; margin-bottom: 12px; }
    .stats-akreditasi { max-width: 420px; margin-left: auto; margin-right: auto; }
    .stat { flex: 1; border: 1px solid #333; padding: 8px; text-align: center; }
    .stat-label { font-size: 9px; text-transform: uppercase; color: #555; }
    .stat-value { font-size: 16px; font-weight: 700; margin-top: 2px; }
    .note { font-size: 10px; color: #444; margin: 0 0 10px; }
    .printed-at { font-size: 9px; color: #555; text-align: center; }
    .sig-wrap { display: table; width: 100%; margin-top: 28px; page-break-inside: avoid; }
    .sig-col { display: table-cell; width: 50%; vertical-align: top; }
    .sig { text-align: center; min-width: 220px; }
    .sig-col-right { text-align: right; }
    .sig-col-right .sig { display: inline-block; text-align: center; }
    .sig-place, .sig-role { font-size: 10px; line-height: 1.35; }
    .sig-space { height: 56px; }
    .sig-name { font-size: 11px; font-weight: 700; text-decoration: underline; }
    .sig-nip { font-size: 9px; margin-top: 2px; }
    .center { text-align: center; }
    .nowrap { white-space: nowrap; }
    .cell-sub { font-size: 9px; color: #555; }
    .stats-compact .stat-value-sm { font-size: 11px; line-height: 1.3; }
    .table-akreditasi th, .table-akreditasi td { font-size: 10px; padding: 5px 6px; }
    .table-akreditasi .col-no { width: 28px; }
    .table-akreditasi .col-nama { width: 14%; }
    .table-akreditasi .col-lomba { width: 18%; }
    .table-akreditasi .col-kat { width: 9%; }
    .table-akreditasi .col-jenis { width: 11%; }
    .table-akreditasi .col-tingkat { width: 10%; }
    .table-akreditasi .col-rank { width: 8%; }
    .table-akreditasi .col-tgl { width: 7%; }
    .table-akreditasi .col-ket { width: 14%; }
    .table-compact { max-width: 360px; }
    .table-trend { max-width: 320px; }
    .split-tables-print { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; }
    .split-tables-print > table { flex: 1; min-width: 200px; }
    @media print {
      @page { size: A4 ${xt}; margin: 10mm 12mm 14mm 10mm; }
      body { margin: 0; padding-right: 1px; }
      .printed-at {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
      }
    }
  </style>
</head>
<body>
  <header class="kop">
    <div class="kop-inner">
      <div>${_.logo?`<img src="${n(_.logo)}" alt="Logo institusi" class="kop-logo" />`:""}</div>
      <div class="kop-text">
        ${_.foundation_name?`<div class="foundation">${n(_.foundation_name)}</div>`:""}
        <div class="school">${n(ft)}</div>
        <div class="school-address">${n(kt||"-")}</div>
        <div class="school-info">
          NPSN: ${n(_.npsn||"-")}
          ${_.nss?` · ${fa(_.level)}: ${n(_.nss)}`:""}
          ${_.phone?` · Telp: ${n(_.phone)}`:""}
          ${_.email?` · Email: ${n(_.email)}`:""}
          ${_.website?` · ${n(_.website)}`:""}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>${n($t)}</h1>
  ${Te()}
  ${Pe()}
  ${Ie()}
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig">
        <div class="sig-place">&nbsp;</div>
        <div class="sig-role">Mengetahui,<br>${n(Ct)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${n(Pt||"___________________")}</div>
        <div class="sig-nip">NIP. ${n(s||"___________________")}</div>
      </div>
    </div>
    <div class="sig-col sig-col-right">
      <div class="sig">
        <div class="sig-place">${n(wt)}</div>
        <div class="sig-role">${n(T)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${n(Tt||"___________________")}</div>
        <div class="sig-nip">NIP. ${n(We||"___________________")}</div>
      </div>
    </div>
  </div>
  <div class="printed-at">${n(St)}</div>
</body>
</html>`,ut=window.open("","_blank");if(!ut){F.error("Gagal","Popup diblokir. Izinkan popup untuk mencetak.");return}ut.document.write(Qe),ut.document.close(),setTimeout(()=>{ut.print(),ut.document.title=rt},250)}catch{F.error("Gagal","Gagal menyiapkan cetak PDF")}finally{mt.value=!1}}async function Ge(){p.value.semester_id="",await ae(),await nt()}async function ae(){var e;try{const t={per_page:200};p.value.academic_year_id&&(t.academic_year_id=p.value.academic_year_id),p.value.semester_id&&(t.semester_id=p.value.semester_id);let r=((e=(await ya.getAll(t)).data)==null?void 0:e.data)||[];if(Y.value&&Ft.value.length){const c=new Set(Ft.value);r=r.filter(v=>c.has(Number(v.id)))}U.value=r,Y.value&&(r.length===1?p.value.class_id=String(r[0].id):p.value.class_id&&!r.some(c=>String(c.id)===String(p.value.class_id))&&(p.value.class_id=r[0]?String(r[0].id):""))}catch{U.value=[]}}async function qe(){var e;try{const t=await $a.getAll({per_page:100});gt.value=((e=t.data)==null?void 0:e.data)||[]}catch{gt.value=[]}}async function Ye(){var e,t,i;try{const r=await ka.getMy();at.value=((e=r.data)==null?void 0:e.data)||r.data,(t=at.value)!=null&&t.active_academic_year_id&&(p.value.academic_year_id=String(at.value.active_academic_year_id)),(i=at.value)!=null&&i.active_semester_id&&(p.value.semester_id=String(at.value.active_semester_id))}catch{}}Xe(async()=>{await Promise.all([Ye(),Ht.getAcademicYears(),qe()]),await ae();const e=H.query.class_id?String(H.query.class_id):"";e&&U.value.some(r=>String(r.id)===e)&&(p.value.class_id=e);const t=String(H.query.tab||"");t==="detail"||t==="skor"?h.value="skor":t==="catatan"?h.value="catatan":(x.value||k.value&&h.value==="catatan"&&!t)&&(h.value="ringkasan");const i=String(H.query.notes||"");["violations","achievements","counseling"].includes(i)?P.value=i:x.value?P.value="achievements":k.value&&(P.value="violations"),x.value&&h.value==="skor"&&(h.value="ringkasan"),G.value?await Q():await st()});function Ze(){x.value?(P.value="achievements",h.value==="skor"&&(h.value="ringkasan")):k.value&&(P.value="violations")}return se(()=>H.path,async()=>{Ze(),jt(),G.value?await Q():await st()}),se(()=>H.meta.reportPurpose,async()=>{jt(),G.value?await Q():await st()}),(e,t)=>{var i,r,c,v,d,$,b,D,K,j,V,J,C,w,M,_,ft,kt,yt,$t,xt,St,wt,rt,Ct,Pt;return o(),l("div",wa,[t[55]||(t[55]=a("svg",{xmlns:"http://www.w3.org/2000/svg",class:"icon-sprite","aria-hidden":"true"},[a("symbol",{id:"bk-empty",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("div",Ca,[t[19]||(t[19]=a("div",{class:"toolbar-spacer"},null,-1)),a("div",Pa,[a("button",{type:"button",class:"btn-secondary btn-compact",disabled:mt.value||O.value,onClick:Ue},[t[17]||(t[17]=X('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-16f16aeb><path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-16f16aeb></path><path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-16f16aeb></path><path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-16f16aeb></path><path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-16f16aeb></path></svg>',1)),a("span",null,u(mt.value?"Menyiapkan...":"Cetak PDF"),1)],8,Ta),a("button",{type:"button",class:"btn-secondary btn-compact",disabled:ht.value||O.value,onClick:Ne},[t[18]||(t[18]=a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[a("path",{d:"M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M7 10L12 15L17 10",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M12 15V3",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})],-1)),a("span",null,u(ht.value?"Mengekspor...":"Export CSV"),1)],8,La)])]),Vt.value.length>1?(o(),l("nav",Aa,[(o(!0),l(S,null,B(Vt.value,s=>(o(),l("button",{key:s.to,type:"button",class:z(["sub-nav-btn",{active:re(s)}]),onClick:T=>ue(s.to)},u(s.label),11,Ba))),128))])):f("",!0),a("div",Da,[a("nav",Na,[a("button",{type:"button",class:z(["sec-btn",{active:h.value==="ringkasan"}]),onClick:t[0]||(t[0]=s=>ot("ringkasan"))},[...t[20]||(t[20]=[X('<span class="sec-icon" aria-hidden="true" data-v-16f16aeb><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-16f16aeb><path d="M4 19V5M4 19h16M8 16V9M12 16V7M16 16v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-16f16aeb></path></svg></span><span class="sec-text" data-v-16f16aeb><span class="sec-label" data-v-16f16aeb>Ringkasan</span><span class="sec-hint" data-v-16f16aeb>Rekap kelas &amp; tren</span></span>',2)])],2),L.value||k.value?(o(),l("button",{key:0,type:"button",class:z(["sec-btn",{active:h.value==="skor"}]),onClick:t[1]||(t[1]=s=>ot("skor"))},[...t[21]||(t[21]=[X('<span class="sec-icon" aria-hidden="true" data-v-16f16aeb><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-16f16aeb><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" data-v-16f16aeb></path><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" data-v-16f16aeb></path></svg></span><span class="sec-text" data-v-16f16aeb><span class="sec-label" data-v-16f16aeb>Skor siswa</span><span class="sec-hint" data-v-16f16aeb>Poin per siswa</span></span>',2)])],2)):f("",!0),a("button",{type:"button",class:z(["sec-btn",{active:h.value==="catatan"}]),onClick:t[2]||(t[2]=s=>ot("catatan"))},[t[23]||(t[23]=a("span",{class:"sec-icon","aria-hidden":"true"},[a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M4 7h16M4 12h10M4 17h7",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",Ra,[t[22]||(t[22]=a("span",{class:"sec-label"},"Catatan",-1)),a("span",Ka,u(x.value?"Prestasi siswa":k.value?"Pelanggaran siswa":"Pelanggaran, prestasi, konseling"),1)])],2)]),a("div",ja,[a("p",Ma,u(_e.value),1),h.value==="catatan"&&(L.value||k.value||x.value)?(o(),l("div",Ha,[x.value?f("",!0):(o(),l("button",{key:0,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="violations"}]),"aria-selected":P.value==="violations",onClick:t[3]||(t[3]=s=>P.value="violations")}," Pelanggaran ("+u(((i=g.value)==null?void 0:i.total)??0)+") ",11,Va)),k.value?f("",!0):(o(),l("button",{key:1,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="achievements"}]),"aria-selected":P.value==="achievements",onClick:t[4]||(t[4]=s=>P.value="achievements")}," Prestasi ("+u(((r=g.value)==null?void 0:r.achievements_total)??((v=(c=g.value)==null?void 0:c.achievements)==null?void 0:v.length)??0)+") ",11,Ja)),L.value?(o(),l("button",{key:2,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="counseling"}]),"aria-selected":P.value==="counseling",onClick:t[5]||(t[5]=s=>P.value="counseling")}," Konseling ("+u(((d=g.value)==null?void 0:d.counseling_total)??((b=($=g.value)==null?void 0:$.counseling)==null?void 0:b.length)??0)+") ",11,za)):f("",!0)])):f("",!0),a("div",Ea,[tt(a("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>p.value.academic_year_id=s),class:"filter-select",onChange:Ge},[t[24]||(t[24]=a("option",{value:""},"Semua Tahun Ajaran",-1)),(o(!0),l(S,null,B(Nt.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Ia))),128))],544),[[it,p.value.academic_year_id]]),tt(a("select",{"onUpdate:modelValue":t[7]||(t[7]=s=>p.value.semester_id=s),class:"filter-select",onChange:nt},[t[25]||(t[25]=a("option",{value:""},"Semua Semester",-1)),(o(!0),l(S,null,B(Rt.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Fa))),128))],544),[[it,p.value.semester_id]]),tt(a("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>p.value.class_id=s),class:"filter-select",onChange:nt,disabled:Y.value&&U.value.length<=1},[Y.value?f("",!0):(o(),l("option",Ua,"Semua Kelas")),(o(!0),l(S,null,B(U.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Ga))),128))],40,Oa),[[it,p.value.class_id]]),a("button",{type:"button",class:"filter-toggle","aria-expanded":E.value,onClick:t[9]||(t[9]=s=>E.value=!E.value)},[et(u(E.value?"Sembunyikan filter":"Filter lanjutan")+" ",1),a("span",Ya,u(E.value?"▼":"▶"),1)],8,qa)]),tt(a("div",Za,[tt(a("select",{"onUpdate:modelValue":t[10]||(t[10]=s=>p.value.month=s),class:"filter-select",onChange:nt},[t[26]||(t[26]=a("option",{value:""},"Semua Bulan",-1)),(o(),l(S,null,B(12,s=>a("option",{key:s,value:String(s)},u(Z(s)),9,Wa)),64))],544),[[it,p.value.month]]),tt(a("select",{"onUpdate:modelValue":t[11]||(t[11]=s=>p.value.year=s),class:"filter-select",onChange:nt,title:"Tahun kalender"},[(o(),l(S,null,B(Ot,s=>a("option",{key:s,value:String(s)},"Tahun "+u(s),9,Qa)),64))],544),[[it,p.value.year]])],512),[[ta,E.value]]),Wt.value.length?(o(),l("div",Xa,[(o(!0),l(S,null,B(Wt.value,s=>(o(),l("button",{key:s.key,type:"button",class:"filter-chip",disabled:s.disabled,onClick:T=>De(s.key)},[et(u(s.label)+" ",1),s.disabled?f("",!0):(o(),l("span",es,"×"))],8,ts))),128))])):f("",!0),O.value?(o(),l("div",as,[ne(va,{type:"table",rows:8,columns:7,"cell-widths":["90px","140px","160px","100px","80px","80px","1fr"]})])):h.value==="ringkasan"&&m.value?(o(),l(S,{key:3},[a("div",ss,[x.value?f("",!0):(o(),l("button",{key:0,type:"button",class:"summary-card card-warning",onClick:t[12]||(t[12]=s=>Mt("violations"))},[t[27]||(t[27]=a("span",{class:"summary-icon summary-icon-warning","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 9v4M12 17h.01",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"}),a("path",{d:"M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z",stroke:"currentColor","stroke-width":"2","stroke-linejoin":"round"})])],-1)),a("span",ns,[a("span",is,u(((D=m.value.summary)==null?void 0:D.total_violations)??0),1),a("span",ls,"Pelanggaran · "+u(((K=m.value.summary)==null?void 0:K.total_violation_points)??0)+" poin",1)])])),k.value?f("",!0):(o(),l("button",{key:1,type:"button",class:"summary-card card-good",onClick:t[13]||(t[13]=s=>Mt("achievements"))},[t[28]||(t[28]=a("span",{class:"summary-icon summary-icon-good","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 2l2.4 6.9H22l-5.6 4.1 2.1 6.9L12 16.8 5.5 19.9 7.6 13 2 8.9h7.6L12 2z",stroke:"currentColor","stroke-width":"1.6","stroke-linejoin":"round"})])],-1)),a("span",os,[a("span",rs,u(((j=m.value.summary)==null?void 0:j.total_achievements)??0),1),a("span",us,u(A.value?"Apresiasi":"Prestasi")+" · −"+u(((V=m.value.summary)==null?void 0:V.total_achievement_points)??0)+" poin",1)])])),L.value?(o(),l("button",{key:2,type:"button",class:"summary-card card-score",onClick:t[14]||(t[14]=s=>ot("skor"))},[t[30]||(t[30]=a("span",{class:"summary-icon summary-icon-score","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("circle",{cx:"12",cy:"12",r:"9",stroke:"currentColor","stroke-width":"2"}),a("path",{d:"M12 7v5l3 2",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",ds,[a("span",cs,u(((J=m.value.summary)==null?void 0:J.net_score)??0),1),t[29]||(t[29]=a("span",{class:"summary-label"},"Skor bersih",-1))])])):f("",!0),L.value?(o(),l("button",{key:3,type:"button",class:"summary-card card-total",onClick:t[15]||(t[15]=s=>Mt("counseling"))},[t[32]||(t[32]=a("span",{class:"summary-icon summary-icon-total","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("span",ps,[a("span",vs,u(((C=m.value.summary)==null?void 0:C.total_counseling)??0),1),t[31]||(t[31]=a("span",{class:"summary-label"},"Sesi konseling",-1))])])):f("",!0)]),a("section",hs,[t[37]||(t[37]=a("h3",{class:"section-title"},"Per kelas",-1)),(w=m.value.by_class)!=null&&w.length?(o(),l("div",_s,[a("table",bs,[a("thead",null,[a("tr",null,[t[35]||(t[35]=a("th",null,"Kelas",-1)),x.value?f("",!0):(o(),l("th",fs,"Pelanggaran")),k.value?f("",!0):(o(),l("th",ks,"Prestasi")),L.value?(o(),l("th",ys,"Konseling")):f("",!0),t[36]||(t[36]=a("th",null,null,-1))])]),a("tbody",null,[(o(!0),l(S,null,B(m.value.by_class,s=>(o(),l("tr",{key:s.class_id??s.class_name,class:"row-clickable",onClick:T=>ee(s)},[a("td",null,u(s.class_name),1),x.value?f("",!0):(o(),l("td",xs,u(s.violation_count),1)),k.value?f("",!0):(o(),l("td",Ss,u(s.achievement_count??0),1)),L.value?(o(),l("td",ws,u(s.counseling_count),1)):f("",!0),a("td",Cs,[a("button",{type:"button",class:"link-btn",onClick:ea(T=>ee(s),["stop"])},u(x.value?"Lihat prestasi →":(k.value,"Skor siswa →")),9,Ps)])],8,$s))),128))])])])):(o(),l("div",ms,[t[33]||(t[33]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),t[34]||(t[34]=a("h3",{class:"empty-title"},"Belum ada data",-1)),a("p",gs,u(A.value?"Tidak ada apresiasi untuk filter yang dipilih.":x.value?"Tidak ada prestasi untuk filter yang dipilih.":k.value?"Tidak ada pelanggaran untuk filter yang dipilih.":"Tidak ada pelanggaran, prestasi, atau konseling untuk filter yang dipilih."),1)]))]),a("section",Ts,[a("div",Ls,[a("h3",As,"Tren "+u((M=m.value.by_month)==null?void 0:M.year),1),tt(a("select",{"onUpdate:modelValue":t[16]||(t[16]=s=>p.value.year=s),class:"filter-select filter-select-sm",onChange:Be,title:"Tahun kalender untuk grafik tren"},[(o(),l(S,null,B(Ot,s=>a("option",{key:"t"+s,value:String(s)},u(s),9,Bs)),64))],544),[[it,p.value.year]])]),te.value?(o(),l("div",Ds,[a("div",Ns,[ne(aa(pa),{data:te.value,options:Le},null,8,["data"])])])):f("",!0)]),a("div",Rs,[x.value?f("",!0):(o(),l("section",Ks,[t[39]||(t[39]=a("h3",{class:"section-title"},"Jenis pelanggaran",-1)),(_=m.value.top_violation_types)!=null&&_.length?(o(),l("div",js,[a("table",Ms,[t[38]||(t[38]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(m.value.top_violation_types,s=>(o(),l("tr",{key:s.violation_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Hs,u(s.count),1),a("td",Vs,u(s.total_points),1)]))),128))])])])):(o(),l("p",Js,"Belum ada catatan pelanggaran."))])),k.value?f("",!0):(o(),l("section",zs,[t[41]||(t[41]=a("h3",{class:"section-title"},"Jenis prestasi",-1)),(ft=m.value.top_achievement_types)!=null&&ft.length?(o(),l("div",Es,[a("table",Is,[t[40]||(t[40]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(m.value.top_achievement_types,s=>(o(),l("tr",{key:s.achievement_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Fs,u(s.count),1),a("td",Os,u(s.total_points),1)]))),128))])])])):(o(),l("p",Us,"Belum ada catatan prestasi."))]))])],64)):h.value==="skor"?(o(),l(S,{key:4},[(yt=(kt=g.value)==null?void 0:kt.by_student)!=null&&yt.length?(o(),l("div",qs,[a("table",Ys,[t[43]||(t[43]=a("thead",null,[a("tr",null,[a("th",null,"#"),a("th",null,"NIS"),a("th",null,"Nama"),a("th",{class:"th-num"},"Pelanggaran"),a("th",{class:"th-num"},"Prestasi"),a("th",{class:"th-num"},"Skor")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(Zt.value,s=>(o(),l(S,{key:"bk-"+s.key},[a("tr",Zs,[a("td",Ws,"Kelas "+u(s.name)+" · "+u(s.rows.length)+" siswa",1)]),(o(!0),l(S,null,B(s.rows,(T,Tt)=>(o(),l("tr",{key:T.student_id??s.key+"-"+Tt},[a("td",null,u(s.start+Tt+1),1),a("td",null,u(T.nis||"—"),1),a("td",null,u(T.student_name||"—"),1),a("td",Qs,[et(u(T.violation_points??T.total_points)+" ",1),a("span",Xs,"("+u(T.violation_count)+")",1)]),a("td",tn,[et("−"+u(T.achievement_points??0)+" ",1),a("span",en,"("+u(T.achievement_count??0)+")",1)]),a("td",{class:z(["td-num td-total",ke(T.score)])},u(T.score??(T.violation_points??T.total_points)-(T.achievement_points??0)),3)]))),128))],64))),128))])])])):(o(),l("div",Gs,[...t[42]||(t[42]=[X('<div class="empty-icon" data-v-16f16aeb><svg width="80" height="80" aria-hidden="true" data-v-16f16aeb><use href="#bk-empty" data-v-16f16aeb></use></svg></div><h3 class="empty-title" data-v-16f16aeb>Belum ada data</h3><p class="empty-desc" data-v-16f16aeb>Tidak ada siswa dengan pelanggaran atau prestasi untuk filter ini.</p>',3)])]))],64)):h.value==="catatan"?(o(),l(S,{key:5},[($t=g.value)!=null&&$t.truncated?(o(),l("p",an,"Ditampilkan maksimal 2000 baris per jenis catatan.")):f("",!0),P.value==="violations"?(o(),l("section",sn,[(St=(xt=g.value)==null?void 0:xt.items)!=null&&St.length?(o(),l("div",ln,[a("table",on,[t[45]||(t[45]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",{class:"th-num"},"Poin"),a("th",null,"Status")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(g.value.items,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.violation_date)),1),a("td",null,[a("span",rn,u(s.student_name||"—"),1),a("span",un,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.violation_type),1),a("td",dn,u(s.point_weight),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(Gt(s.status)),3)])]))),128))])])])):(o(),l("div",nn,[...t[44]||(t[44]=[X('<div class="empty-icon" data-v-16f16aeb><svg width="80" height="80" aria-hidden="true" data-v-16f16aeb><use href="#bk-empty" data-v-16f16aeb></use></svg></div><h3 class="empty-title" data-v-16f16aeb>Belum ada pelanggaran</h3><p class="empty-desc" data-v-16f16aeb>Tidak ada catatan untuk filter yang dipilih.</p>',3)])]))])):P.value==="achievements"?(o(),l("section",cn,[(rt=(wt=g.value)==null?void 0:wt.achievements)!=null&&rt.length?(o(),l("div",mn,[a("table",gn,[a("thead",null,[a("tr",null,[t[47]||(t[47]=a("th",null,"Tanggal",-1)),t[48]||(t[48]=a("th",null,"Siswa",-1)),t[49]||(t[49]=a("th",null,"Kelas",-1)),a("th",null,u(A.value?"Uraian":"Lomba / Kegiatan"),1),t[50]||(t[50]=a("th",null,"Jenis",-1)),At.value?(o(),l("th",_n,"Tingkat")):f("",!0),t[51]||(t[51]=a("th",{class:"th-num"},"Poin",-1))])]),a("tbody",null,[(o(!0),l(S,null,B(g.value.achievements,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.achievement_date)),1),a("td",null,[a("span",bn,u(s.student_name||"—"),1),a("span",fn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.title||s.notes||"—"),1),a("td",null,u(s.achievement_type),1),At.value?(o(),l("td",kn,[(s.purpose||"akreditasi")==="akreditasi"?(o(),l(S,{key:0},[et(u(Bt(s.level)),1),s.rank?(o(),l(S,{key:0},[et(" · "+u(Dt(s.rank)),1)],64)):f("",!0)],64)):(o(),l(S,{key:1},[et("—")],64))])):f("",!0),a("td",yn,"−"+u(s.point_value),1)]))),128))])])])):(o(),l("div",pn,[t[46]||(t[46]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),a("h3",vn,"Belum ada "+u(A.value?"apresiasi":"prestasi"),1),a("p",hn,"Tidak ada catatan "+u(A.value?"apresiasi":"prestasi")+" untuk filter yang dipilih.",1)]))])):(o(),l("section",$n,[(Pt=(Ct=g.value)==null?void 0:Ct.counseling)!=null&&Pt.length?(o(),l("div",Sn,[a("table",wn,[t[53]||(t[53]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",null,"Status"),a("th",null,"Konselor")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(g.value.counseling,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.session_date)),1),a("td",null,[a("span",Cn,u(s.student_name||"—"),1),a("span",Pn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.counseling_type),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(qt(s.status)),3)]),a("td",null,u(s.counselor_name||"—"),1)]))),128))])])])):(o(),l("div",xn,[...t[52]||(t[52]=[X('<div class="empty-icon" data-v-16f16aeb><svg width="80" height="80" aria-hidden="true" data-v-16f16aeb><use href="#bk-empty" data-v-16f16aeb></use></svg></div><h3 class="empty-title" data-v-16f16aeb>Belum ada konseling</h3><p class="empty-desc" data-v-16f16aeb>Tidak ada sesi konseling untuk filter yang dipilih.</p>',3)])]))]))],64)):O.value?f("",!0):(o(),l("div",Tn,[...t[54]||(t[54]=[X('<div class="empty-icon" data-v-16f16aeb><svg width="80" height="80" aria-hidden="true" data-v-16f16aeb><use href="#bk-empty" data-v-16f16aeb></use></svg></div><h3 class="empty-title" data-v-16f16aeb>Gagal memuat laporan</h3><p class="empty-desc" data-v-16f16aeb>Coba ubah filter atau periksa koneksi.</p>',3)])]))])])])}}},Fn=ha(Ln,[["__scopeId","data-v-16f16aeb"]]);export{Fn as default};
