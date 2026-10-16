import{l as y,r as N,o as ea,w as ne,D as l,B as o,G as a,H as f,Y as X,I as u,K as S,E as B,F as z,P as tt,$ as it,R as et,Z as aa,O as ie,J as sa,x as na}from"./vue-hEugL7rc.js";import{b as ia,u as la}from"./vue-router-CjKDwc7K.js";import{C as oa,a as ra,L as ua,b as da,p as ca,e as pa,f as va,g as ha}from"./chart-ou4V6FCa.js";import{L as ma}from"./LoadingSkeleton-VfdgTHln.js";import{m as Lt,_ as ga,u as _a,h as ba,a as fa,w as ka,s as ya}from"./index-DhDM2Vtw.js";import{i as $a}from"./institution-Dq_xlzq8.js";import{c as xa}from"./class-Dt2uKD2v.js";import{s as Sa}from"./semester-DIZObmPP.js";import{u as wa}from"./referenceData-CD02tMrv.js";import{f as Ca}from"./printFooter-DS6rGvsi.js";import"./pinia-cAaNxRQ6.js";import"./axios-C0Zqfgkc.js";import"./academicYear-Bz-UoMyI.js";const dt={getSummary(I){return Lt.get("/v1/bk-reports/summary",{params:I})},getViolationDetail(I){return Lt.get("/v1/bk-reports/violations",{params:I})},export(I){return Lt.get("/v1/bk-reports/export",{params:I,responseType:"blob"})},exportViolations(I){return Lt.get("/v1/bk-reports/export-violations",{params:I,responseType:"blob"})}},Pa={class:"laporan-bk-page"},Ta={class:"toolbar"},La={class:"header-actions toolbar-actions"},Aa=["disabled"],Ba=["disabled"],Da={key:0,class:"report-kind-nav","aria-label":"Jenis laporan"},Ra=["onClick"],Na={class:"tab-shell"},Ka={class:"section-nav","aria-label":"Navigasi laporan BK"},ja={class:"sec-text"},Ma={class:"sec-hint"},Ha={class:"tab-main"},Va={class:"tab-description"},Ja={key:0,class:"sub-nav",role:"tablist","aria-label":"Jenis catatan"},za=["aria-selected"],Ea=["aria-selected"],Ia=["aria-selected"],Fa={class:"filters"},Oa=["value"],Ua=["value"],Ga=["disabled"],qa={key:0,value:""},Ya=["value"],Za=["aria-expanded"],Wa={class:"filter-toggle-icon"},Qa={class:"filters filters-advanced"},Xa=["value"],ts=["value"],es={key:1,class:"filter-chips"},as=["disabled","onClick"],ss={key:0,"aria-hidden":"true"},ns={key:2,class:"loading-wrap"},is={class:"stat-cards"},ls={class:"summary-body"},os={class:"summary-value"},rs={class:"summary-label"},us={class:"summary-body"},ds={class:"summary-value"},cs={class:"summary-label"},ps={class:"summary-body"},vs={class:"summary-value"},hs={class:"summary-body"},ms={class:"summary-value"},gs={class:"report-section"},_s={key:0,class:"empty-state"},bs={class:"empty-desc"},fs={key:1,class:"table-container"},ks={class:"data-table"},ys={key:0,class:"th-num"},$s={key:1,class:"th-num"},xs={key:2,class:"th-num"},Ss=["onClick"],ws={key:0,class:"td-num"},Cs={key:1,class:"td-num td-good"},Ps={key:2,class:"td-num"},Ts={class:"td-action"},Ls=["onClick"],As={class:"report-section"},Bs={class:"section-head"},Ds={class:"section-title"},Rs=["value"],Ns={key:0,class:"chart-box"},Ks={class:"chart-wrap"},js={class:"split-tables"},Ms={key:0,class:"report-section"},Hs={key:0,class:"table-container"},Vs={class:"data-table"},Js={class:"td-num"},zs={class:"td-num"},Es={key:1,class:"empty-desc muted"},Is={key:1,class:"report-section"},Fs={key:0,class:"table-container"},Os={class:"data-table"},Us={class:"td-num"},Gs={class:"td-num td-good"},qs={key:1,class:"empty-desc muted"},Ys={key:0,class:"empty-state"},Zs={key:1,class:"table-container"},Ws={class:"data-table"},Qs={class:"group-row"},Xs={colspan:"6"},tn={class:"td-num"},en={class:"td-muted"},an={class:"td-num td-good"},sn={class:"td-muted"},nn={key:0,class:"section-hint"},ln={key:1,class:"report-section"},on={key:0,class:"empty-state"},rn={key:1,class:"table-container"},un={class:"data-table"},dn={class:"student-name"},cn={class:"student-meta"},pn={class:"td-num"},vn={key:2,class:"report-section"},hn={key:0,class:"empty-state"},mn={class:"empty-title"},gn={class:"empty-desc"},_n={key:1,class:"table-container"},bn={class:"data-table"},fn={key:0},kn={class:"student-name"},yn={class:"student-meta"},$n={key:0},xn={class:"td-num td-good"},Sn={key:3,class:"report-section"},wn={key:0,class:"empty-state"},Cn={key:1,class:"table-container"},Pn={class:"data-table"},Tn={class:"student-name"},Ln={class:"student-meta"},An={key:6,class:"empty-state"},Bn={__name:"CounselingReport",setup(I){oa.register(ra,ua,da,ca,pa,va);const F=fa(),H=ia(),le=la(),Ht=wa(),ct=_a(),oe=y(()=>ba(ct.user,"violation")),re=[{to:"/bk/laporan",label:"Gabungan",scope:"combined"},{to:"/bk/laporan/pelanggaran",label:"Pelanggaran",scope:"violations",requiresViolation:!0},{to:"/bk/laporan/prestasi",label:"Prestasi",scope:"achievements",purpose:"akreditasi",requiresViolation:!0},{to:"/bk/laporan/apresiasi",label:"Apresiasi",scope:"achievements",purpose:"apresiasi",requiresViolation:!0}],Vt=y(()=>re.filter(e=>!e.requiresViolation||oe.value)),pt=y(()=>H.meta.reportScope||"combined"),vt=y(()=>H.meta.reportPurpose||null),L=y(()=>pt.value==="combined"),k=y(()=>pt.value==="violations"),x=y(()=>pt.value==="achievements"),A=y(()=>vt.value==="apresiasi"),R=y(()=>vt.value==="akreditasi"),At=y(()=>!A.value);function ue(e){return e.scope==="combined"?L.value:e.scope==="violations"?k.value:e.purpose==="apresiasi"?A.value:e.purpose==="akreditasi"?R.value:H.path===e.to}function de(e){H.path!==e&&le.push(e)}const Jt=y(()=>A.value?"Laporan Apresiasi":R.value?"Laporan Prestasi":k.value?"Laporan Pelanggaran":"Laporan BK"),zt=y(()=>A.value?"Laporan_Apresiasi":R.value?"Laporan_Prestasi":k.value?"Laporan_Pelanggaran":"Laporan_BK"),ce={sekolah:"Sekolah",kabupaten:"Kabupaten/Kota",provinsi:"Provinsi",nasional:"Nasional",internasional:"Internasional"},pe={juara_1:"Juara 1",juara_2:"Juara 2",juara_3:"Juara 3",finalis:"Finalis",peserta:"Peserta",lainnya:"Lainnya"};function Bt(e){return ce[e]||e||"—"}function Dt(e){return pe[e]||e||""}const ve={akademik:"Akademik",non_akademik:"Non Akademik",sikap:"Sikap"},Et={internasional:1,nasional:2,provinsi:3,kabupaten:4,sekolah:5},he={ringan:"Ringan",sedang:"Sedang",berat:"Berat",sangat_berat:"Sangat Berat"};function me(e){return ve[e]||e||"—"}function It(e){return he[e]||e||"—"}function ge(e){return[...e||[]].sort((t,i)=>{const r=(t.class_name||"").localeCompare(i.class_name||"");if(r!==0)return r;const c=Et[t.level]??6,v=Et[i.level]??6;return c!==v?c-v:(i.achievement_date||"").localeCompare(t.achievement_date||"")})}function _e(e){return Zt(ge(e))}function Ft(e){const t=e||[],i=new Set,r={},c={},v={};for(const d of t){d.student_id&&i.add(d.student_id);const $=d.level||"_kosong";r[$]=(r[$]||0)+1;const b=d.category||"_lainnya";c[b]=(c[b]||0)+1,d.rank&&(v[d.rank]=(v[d.rank]||0)+1)}return{total:t.length,students:i.size,byLevel:r,byCategory:c,byRank:v}}function be(e){return!e||e==="_kosong"?"Belum diisi":Bt(e)}const O=N(!0),ht=N(!1),mt=N(!1),h=N("ringkasan"),P=N("violations"),E=N(!1),m=N(null),g=N(null),q=N({principal:{},bk:{}}),U=N([]),gt=N([]),at=N(null),Y=y(()=>{var e;return((e=ct.user)==null?void 0:e.bk_scope)==="homeroom"}),Ot=y(()=>{var e;return(((e=ct.user)==null?void 0:e.homeroom_class_ids)||[]).map(Number)}),G=y(()=>h.value!=="ringkasan"),fe=y(()=>Y.value?"Menampilkan siswa di kelas yang Anda walikan saja.":x.value?A.value?h.value==="ringkasan"?"Rekap apresiasi dan laporan positif siswa per kelas.":"Daftar apresiasi / laporan siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap prestasi lomba/kompetisi (akreditasi) per kelas, kategori, dan jenis.":"Daftar prestasi akreditasi sesuai filter yang dipilih.":k.value?h.value==="ringkasan"?"Rekap pelanggaran siswa per kelas dan jenis.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi.":"Daftar pelanggaran siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap kelas. Skor bersih = poin pelanggaran − poin prestasi.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.":"Catatan pelanggaran, prestasi, dan konseling sesuai filter yang dipilih."),_t=new Date().getFullYear(),Ut=[_t,_t-1,_t-2],p=N({academic_year_id:"",semester_id:"",class_id:"",month:"",year:String(_t)}),Rt=y(()=>Ht.academicYears||[]),Nt=y(()=>{const e=p.value.academic_year_id;return e?gt.value.filter(t=>String(t.academic_year_id)===String(e)):gt.value}),Gt=["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"],ke={dicatat:"Dicatat",sanksi_diberikan:"Sanksi Diberikan",follow_up:"Follow Up",selesai:"Selesai"},ye={jadwal:"Jadwal",berlangsung:"Berlangsung",selesai:"Selesai",dibatalkan:"Dibatalkan"};function Z(e){return Gt[e]||`Bulan ${e}`}function qt(e){return ke[e]||e||"—"}function Yt(e){return ye[e]||e||"—"}function $e(e){const t=Number(e);return Number.isNaN(t)?"":t>40?"score-bad":t>20?"score-warn":t<=0?"score-good":""}function W(e){if(!e)return"—";const[t,i,r]=e.split("-");return!t||!i||!r?e:`${r}/${i}/${t}`}function Zt(e){const t=[];let i=null,r=0;for(const c of e||[]){const v=c.class_name||"Tanpa kelas";(!i||i.name!==v)&&(i={key:v,name:v,rows:[],start:r},t.push(i)),i.rows.push(c),r+=1}return t}const Wt=y(()=>{var e;return Zt(((e=g.value)==null?void 0:e.by_student)||[])}),lt=y(()=>{const e=U.value.find(t=>String(t.id)===String(p.value.class_id));return(e==null?void 0:e.name)||""}),Qt=y(()=>{const e=[];return p.value.class_id&&lt.value&&e.push({key:"class",label:`Kelas ${lt.value}`,disabled:Y.value&&U.value.length<=1}),E.value&&p.value.month&&e.push({key:"month",label:`${Z(Number(p.value.month))} ${p.value.year}`,disabled:!1}),e}),xe=y(()=>{var r,c;const e=[];e.push(lt.value||"Semua kelas");const t=(r=Rt.value.find(v=>String(v.id)===String(p.value.academic_year_id)))==null?void 0:r.name;t&&e.push(t);const i=(c=Nt.value.find(v=>String(v.id)===String(p.value.semester_id)))==null?void 0:c.name;return i&&e.push(i),E.value&&p.value.month&&e.push(`${Z(Number(p.value.month))} ${p.value.year}`),e.join(" · ")}),Xt=y(()=>{const e=p.value.academic_year_id;return e&&Rt.value.find(t=>String(t.id)===String(e))||null}),Se=y(()=>{var e;return((e=Xt.value)==null?void 0:e.name)||"—"});function we(e){if(e!=null&&e.start_date&&(e!=null&&e.end_date)){const v=new Date(e.start_date),d=new Date(e.end_date);return{startYear:v.getFullYear(),endYear:d.getFullYear()}}const t=String((e==null?void 0:e.name)||"").match(/(\d{4})\s*\/\s*(\d{4})/);if(t)return{startYear:parseInt(t[1],10),endYear:parseInt(t[2],10)};const i=new Date,r=i.getFullYear(),c=i.getMonth()+1>=7?r:r-1;return{startYear:c,endYear:c+1}}function Ce(e,t){const{startYear:i,endYear:r}=we(t),c={};for(const d of e||[]){if(!d.achievement_date)continue;const[$,b]=d.achievement_date.split("-").map(Number);if(!$||!b)continue;const D=`${$}-${b}`;c[D]=(c[D]||0)+1}const v=[];for(let d=0;d<12;d++){const $=(6+d)%12+1,b=$>=7?i:r;v.push({month:$,year:b,label:`${Z($)} ${b}`,count:c[`${b}-${$}`]||0})}return{slots:v,rangeLabel:`${Z(7)} ${i} – ${Z(6)} ${r}`}}function Pe(e){if(!(e!=null&&e.length))return'<tr><td colspan="9">Belum ada data prestasi untuk filter yang dipilih.</td></tr>';const t=_e(e),i=9;return t.flatMap(r=>{const c=`<tr class="group-row"><td colspan="${i}">Kelas ${n(r.name)} · ${r.rows.length} prestasi</td></tr>`,v=r.rows.map((d,$)=>{const b=[d.nis,d.nisn].filter(Boolean).join(" / ")||"—",D=d.notes&&d.title&&d.notes!==d.title?d.notes:d.notes||"—";return`
      <tr>
        <td class="center">${r.start+$+1}</td>
        <td>
          <strong>${n(d.student_name||"—")}</strong><br>
          <span class="cell-sub">${n(b)}</span>
        </td>
        <td>${n(d.title||d.notes||"—")}</td>
        <td>${n(me(d.category))}</td>
        <td>${n(d.achievement_type||"—")}</td>
        <td>${n(be(d.level))}</td>
        <td class="center">${n(d.rank?Dt(d.rank):"—")}</td>
        <td class="center nowrap">${n(W(d.achievement_date))}</td>
        <td>${n(D)}</td>
      </tr>`}).join("");return c+v}).join("")}function te(e,t="Data Prestasi"){return`
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
      <tbody>${Pe(e)}</tbody>
    </table>
  `}function Te(e,t="2. Tren Bulanan"){const{slots:i,rangeLabel:r}=Ce(e,Xt.value),c=i.map(v=>`
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
  `}function Le(){var e;if(R.value&&h.value==="ringkasan"){const t=[],i=(e=Nt.value.find(r=>String(r.id)===String(p.value.semester_id)))==null?void 0:e.name;return i&&t.push(i),lt.value&&t.push(`Kelas ${lt.value}`),t.length?`<div class="period"><strong>Filter tambahan:</strong> ${n(t.join(" · "))}</div>`:""}return`<div class="period"><strong>Periode / Filter:</strong> ${n(xe.value)}</div>`}function Ae(){return R.value&&h.value==="ringkasan"?`<div class="subtitle academic-year">Tahun Ajaran ${n(Se.value)}</div>`:R.value?'<div class="subtitle">Prestasi Lomba & Kompetisi · Bimbingan Konseling</div>':`<div class="subtitle">${n(Jt.value)} · Bimbingan Konseling</div>`}function bt({forDetail:e=!1}={}){const t={academic_year_id:p.value.academic_year_id,semester_id:p.value.semester_id,class_id:p.value.class_id,year:p.value.year},i=E.value&&!!p.value.month;return e?i?t.month=p.value.month:delete t.year:i&&(t.month=p.value.month),Object.keys(t).forEach(r=>{r==="academic_year_id"||r==="semester_id"||(t[r]===""||t[r]===null||t[r]===void 0)&&delete t[r]}),vt.value&&(t.purpose=vt.value),t.report_scope=pt.value,t}const ee=y(()=>{var i,r;const e=(r=(i=m.value)==null?void 0:i.by_month)==null?void 0:r.months;if(!(e!=null&&e.length))return null;const t=[];return x.value||t.push({label:"Pelanggaran",data:e.map(c=>c.violation_count),backgroundColor:"rgba(239, 68, 68, 0.7)",borderRadius:4}),k.value||t.push({label:"Prestasi",data:e.map(c=>c.achievement_count??0),backgroundColor:"rgba(16, 185, 129, 0.7)",borderRadius:4}),L.value&&t.push({label:"Konseling",data:e.map(c=>c.counseling_count),backgroundColor:"rgba(14, 165, 233, 0.7)",borderRadius:4}),t.length?{labels:e.map(c=>c.label||Gt[c.month]),datasets:t}:null}),Be={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom"}},scales:{y:{beginAtZero:!0,ticks:{precision:0}}}};function Kt(e){e!=null&&e.signers&&(q.value=e.signers)}async function st(){var e;O.value=!0;try{const t=await dt.getSummary(bt({forDetail:!1}));m.value=((e=t.data)==null?void 0:e.data)??null,Kt(m.value)}catch(t){m.value=null,F.error("Gagal memuat ringkasan",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}async function De(){var e;if(!(g.value&&Array.isArray(g.value.achievements)))try{const t=await dt.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Kt(g.value)}catch{}}async function Q(){var e;O.value=!0;try{const t=await dt.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Kt(g.value)}catch(t){g.value=null,F.error("Gagal memuat detail",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}function jt(){m.value=null,g.value=null}async function nt(){jt(),G.value?await Q():await st()}async function Re(){m.value=null,await st()}async function ot(e){h.value!==e&&(h.value=e,e==="ringkasan"?m.value||await st():g.value||await Q())}async function Mt(e){P.value=e,await ot("catatan")}async function ae(e){if(e.class_id&&(p.value.class_id=String(e.class_id)),m.value=null,g.value=null,x.value){P.value="achievements",h.value="catatan",await Q();return}h.value="skor",await Q()}async function Ne(e){if(e==="class"){if(Y.value&&U.value.length<=1)return;p.value.class_id=""}e==="month"&&(p.value.month=""),await nt()}async function Ke(){ht.value=!0;try{const e=G.value,t=bt({forDetail:e});e&&(t.export_view=h.value,L.value&&h.value==="catatan"&&(t.notes_kind=P.value));const i=e?await dt.exportViolations(t):await dt.export(t),r=window.URL.createObjectURL(new Blob([i.data])),c=document.createElement("a");c.href=r;const v=zt.value.replace(/_/g,"-").toLowerCase();c.setAttribute("download",e?`${v}-detail-${new Date().toISOString().slice(0,10)}.csv`:`${v}-per-kelas-${new Date().toISOString().slice(0,10)}.csv`),document.body.appendChild(c),c.click(),c.remove(),window.URL.revokeObjectURL(r),F.success("Export berhasil diunduh")}catch(e){F.error("Gagal mengekspor",e.formattedMessage||"Data tidak dapat diekspor.")}finally{ht.value=!1}}function n(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function je(){if(!g.value)return"<p>Tidak ada data.</p>";const e=!k.value,t=e?9:7,i=(Wt.value||[]).flatMap(v=>{const d=`<tr class="group-row"><td colspan="${t}">Kelas ${n(v.name)} (${v.rows.length} siswa)</td></tr>`,$=v.rows.map((b,D)=>{const K=b.violation_points??b.total_points??0,j=b.achievement_points??0,V=b.score??K-j,J=e?`<td class="num">${n(b.achievement_count??0)}</td>
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
  `}function Me(){var t;return`
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
      <td>${n(It(i.category))}</td>
      <td class="num">${n(i.point_weight)}</td>
      <td>${n(qt(i.status))}</td>
      <td>${n(i.reporter_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="9">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function He(){var v;if(R.value)return Ve();const e=At.value,t=A.value?"Uraian":"Lomba / Kegiatan",i=A.value?"Daftar Apresiasi":"Daftar Prestasi",r=e?"<th>Tingkat</th>":"",c=(((v=g.value)==null?void 0:v.achievements)||[]).map(d=>{const $=(d.purpose||"akreditasi")==="akreditasi",b=e?`<td>${$?`${n(Bt(d.level))}${d.rank?` · ${n(Dt(d.rank))}`:""}`:"—"}</td>`:"";return`
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
  `}function Ve(){var i;const e=((i=g.value)==null?void 0:i.achievements)||[];if(!e.length)return"<p>Belum ada data prestasi untuk filter yang dipilih.</p>";const t=Ft(e);return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${t.total}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${t.students}</div></div>
    </div>
    ${te(e,"Daftar Prestasi per Siswa")}
  `}function Je(){var v,d;const e=((v=g.value)==null?void 0:v.achievements)||[],t=Ft(e),i=((d=m.value)==null?void 0:d.summary)||{},r=e.length?t.total:i.total_achievements??0,c=t.students;return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${r}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${c}</div></div>
    </div>
    ${te(e,"1. Data Prestasi")}
    ${Te(e,"2. Tren Bulanan")}
  `}function ze(){var t;return`
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
      <td>${n(Yt(i.status))}</td>
      <td>${n(i.counselor_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="7">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function Ee(){return k.value?"violations":x.value?"achievements":P.value}function Ie(){if(!g.value)return"<p>Tidak ada data.</p>";const e=Ee();return e==="violations"?Me():e==="achievements"?He():ze()}function Fe(){var b,D,K,j,V,J;if(R.value)return!m.value&&!((D=(b=g.value)==null?void 0:b.achievements)!=null&&D.length)?"<p>Tidak ada data.</p>":Je();if(!m.value)return"<p>Tidak ada data.</p>";const e=m.value.summary||{},t=[];let i=1;const r=[];if(x.value||r.push(`<div class="stat"><div class="stat-label">Total Pelanggaran</div><div class="stat-value">${e.total_violations??0}</div></div>`),!k.value){const C=A.value?"Total Apresiasi":"Total Prestasi";r.push(`<div class="stat"><div class="stat-label">${C}</div><div class="stat-value">${e.total_achievements??0}</div></div>`)}L.value&&(r.push(`<div class="stat"><div class="stat-label">Skor Bersih</div><div class="stat-value">${e.net_score??0}</div></div>`),r.push(`<div class="stat"><div class="stat-label">Total Konseling</div><div class="stat-value">${e.total_counseling??0}</div></div>`)),r.length&&t.push(`<div class="stats">${r.join("")}</div>`),L.value&&t.push('<p class="note">Skor bersih = poin pelanggaran − poin prestasi.</p>');const c=["<th>Kelas</th>"];x.value||c.push("<th>Pelanggaran</th>"),k.value||c.push(`<th>${A.value?"Apresiasi":"Prestasi"}</th>`),L.value&&c.push("<th>Konseling</th>");const v=(m.value.by_class||[]).map(C=>{const w=[`<td>${n(C.class_name)}</td>`];return x.value||w.push(`<td class="num">${n(C.violation_count)}</td>`),k.value||w.push(`<td class="num">${n(C.achievement_count??0)}</td>`),L.value&&w.push(`<td class="num">${n(C.counseling_count)}</td>`),`<tr>${w.join("")}</tr>`}).join("")||`<tr><td colspan="${c.length}">Belum ada data</td></tr>`;t.push(`
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
        <td>${n(It(w.category))}</td>
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
    `)}return t.join("")}function Oe(){return h.value==="ringkasan"?Fe():h.value==="skor"?je():Ie()}function Ue(){return R.value||G.value?"landscape":"portrait"}function Ge(){if(R.value){if(h.value==="ringkasan")return"Rekapitulasi Prestasi Siswa";if(h.value==="catatan")return"Daftar Prestasi Siswa — Lomba & Kompetisi"}const e=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor Siswa":"Catatan";return`${Jt.value} — ${e}`}async function qe(){var t,i,r,c,v,d,$,b,D,K,j,V,J,C,w;if(!(G.value?!!g.value:!!m.value)&&!R.value){F.error("Gagal","Tidak ada data untuk dicetak");return}mt.value=!0;try{if(R.value&&await De(),!(R.value?!!(m.value||(i=(t=g.value)==null?void 0:t.achievements)!=null&&i.length):G.value?!!g.value:!!m.value)){F.error("Gagal","Tidak ada data untuk dicetak");return}const _=at.value||{},ft=_.name||"Sekolah",kt=[_.address,_.village?`Desa/Kel. ${_.village}`:"",_.sub_district?`Kec. ${_.sub_district}`:"",_.district,_.province,_.postal_code].filter(Boolean).join(", "),yt=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor_Siswa":"Catatan",$t=Ge(),xt=Ue(),St=Ca((r=ct.user)==null?void 0:r.name),wt=`${_.district||_.city||"........................"}, ${new Date().toLocaleDateString("id-ID",{day:"numeric",month:"long",year:"numeric"})}`,rt=`${zt.value}_${yt}_${new Date().toISOString().slice(0,10)}.pdf`,Ct=((v=(c=q.value)==null?void 0:c.principal)==null?void 0:v.role)||ka(_.level),Pt=(($=(d=q.value)==null?void 0:d.principal)==null?void 0:$.name)||_.principal_name||"",s=((D=(b=q.value)==null?void 0:b.principal)==null?void 0:D.nip)||_.principal_nip||"",T=((j=(K=q.value)==null?void 0:K.bk)==null?void 0:j.role)||"Guru Bimbingan Konseling",Tt=((J=(V=q.value)==null?void 0:V.bk)==null?void 0:J.name)||"",Xe=((w=(C=q.value)==null?void 0:C.bk)==null?void 0:w.nip)||"",ta=`<!DOCTYPE html>
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
          ${_.nss?` · ${ya(_.level)}: ${n(_.nss)}`:""}
          ${_.phone?` · Telp: ${n(_.phone)}`:""}
          ${_.email?` · Email: ${n(_.email)}`:""}
          ${_.website?` · ${n(_.website)}`:""}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>${n($t)}</h1>
  ${Ae()}
  ${Le()}
  ${Oe()}
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
        <div class="sig-nip">NIP. ${n(Xe||"___________________")}</div>
      </div>
    </div>
  </div>
  <div class="printed-at">${n(St)}</div>
</body>
</html>`,ut=window.open("","_blank");if(!ut){F.error("Gagal","Popup diblokir. Izinkan popup untuk mencetak.");return}ut.document.write(ta),ut.document.close(),setTimeout(()=>{ut.print(),ut.document.title=rt},250)}catch{F.error("Gagal","Gagal menyiapkan cetak PDF")}finally{mt.value=!1}}async function Ye(){p.value.semester_id="",await se(),await nt()}async function se(){var e;try{const t={per_page:200};p.value.academic_year_id&&(t.academic_year_id=p.value.academic_year_id),p.value.semester_id&&(t.semester_id=p.value.semester_id);let r=((e=(await xa.getAll(t)).data)==null?void 0:e.data)||[];if(Y.value&&Ot.value.length){const c=new Set(Ot.value);r=r.filter(v=>c.has(Number(v.id)))}U.value=r,Y.value&&(r.length===1?p.value.class_id=String(r[0].id):p.value.class_id&&!r.some(c=>String(c.id)===String(p.value.class_id))&&(p.value.class_id=r[0]?String(r[0].id):""))}catch{U.value=[]}}async function Ze(){var e;try{const t=await Sa.getAll({per_page:100});gt.value=((e=t.data)==null?void 0:e.data)||[]}catch{gt.value=[]}}async function We(){var e,t,i;try{const r=await $a.getMy();at.value=((e=r.data)==null?void 0:e.data)||r.data,(t=at.value)!=null&&t.active_academic_year_id&&(p.value.academic_year_id=String(at.value.active_academic_year_id)),(i=at.value)!=null&&i.active_semester_id&&(p.value.semester_id=String(at.value.active_semester_id))}catch{}}ea(async()=>{await Promise.all([We(),Ht.getAcademicYears(),Ze()]),await se();const e=H.query.class_id?String(H.query.class_id):"";e&&U.value.some(r=>String(r.id)===e)&&(p.value.class_id=e);const t=String(H.query.tab||"");t==="detail"||t==="skor"?h.value="skor":t==="catatan"?h.value="catatan":(x.value||k.value&&h.value==="catatan"&&!t)&&(h.value="ringkasan");const i=String(H.query.notes||"");["violations","achievements","counseling"].includes(i)?P.value=i:x.value?P.value="achievements":k.value&&(P.value="violations"),x.value&&h.value==="skor"&&(h.value="ringkasan"),G.value?await Q():await st()});function Qe(){x.value?(P.value="achievements",h.value==="skor"&&(h.value="ringkasan")):k.value&&(P.value="violations")}return ne(()=>H.path,async()=>{Qe(),jt(),G.value?await Q():await st()}),ne(()=>H.meta.reportPurpose,async()=>{jt(),G.value?await Q():await st()}),(e,t)=>{var i,r,c,v,d,$,b,D,K,j,V,J,C,w,M,_,ft,kt,yt,$t,xt,St,wt,rt,Ct,Pt;return o(),l("div",Pa,[t[55]||(t[55]=a("svg",{xmlns:"http://www.w3.org/2000/svg",class:"icon-sprite","aria-hidden":"true"},[a("symbol",{id:"bk-empty",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("div",Ta,[t[19]||(t[19]=a("div",{class:"toolbar-spacer"},null,-1)),a("div",La,[a("button",{type:"button",class:"btn-secondary btn-compact",disabled:mt.value||O.value,onClick:qe},[t[17]||(t[17]=X('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-2eb0fca4><path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-2eb0fca4></path><path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-2eb0fca4></path><path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-2eb0fca4></path><path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-2eb0fca4></path></svg>',1)),a("span",null,u(mt.value?"Menyiapkan...":"Cetak PDF"),1)],8,Aa),a("button",{type:"button",class:"btn-secondary btn-compact",disabled:ht.value||O.value,onClick:Ke},[t[18]||(t[18]=a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[a("path",{d:"M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M7 10L12 15L17 10",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M12 15V3",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})],-1)),a("span",null,u(ht.value?"Mengekspor...":"Export CSV"),1)],8,Ba)])]),Vt.value.length>1?(o(),l("nav",Da,[(o(!0),l(S,null,B(Vt.value,s=>(o(),l("button",{key:s.to,type:"button",class:z(["sub-nav-btn",{active:ue(s)}]),onClick:T=>de(s.to)},u(s.label),11,Ra))),128))])):f("",!0),a("div",Na,[a("nav",Ka,[a("button",{type:"button",class:z(["sec-btn",{active:h.value==="ringkasan"}]),onClick:t[0]||(t[0]=s=>ot("ringkasan"))},[...t[20]||(t[20]=[X('<span class="sec-icon" aria-hidden="true" data-v-2eb0fca4><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-2eb0fca4><path d="M4 19V5M4 19h16M8 16V9M12 16V7M16 16v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-2eb0fca4></path></svg></span><span class="sec-text" data-v-2eb0fca4><span class="sec-label" data-v-2eb0fca4>Ringkasan</span><span class="sec-hint" data-v-2eb0fca4>Rekap kelas &amp; tren</span></span>',2)])],2),L.value||k.value?(o(),l("button",{key:0,type:"button",class:z(["sec-btn",{active:h.value==="skor"}]),onClick:t[1]||(t[1]=s=>ot("skor"))},[...t[21]||(t[21]=[X('<span class="sec-icon" aria-hidden="true" data-v-2eb0fca4><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-2eb0fca4><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" data-v-2eb0fca4></path><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" data-v-2eb0fca4></path></svg></span><span class="sec-text" data-v-2eb0fca4><span class="sec-label" data-v-2eb0fca4>Skor siswa</span><span class="sec-hint" data-v-2eb0fca4>Poin per siswa</span></span>',2)])],2)):f("",!0),a("button",{type:"button",class:z(["sec-btn",{active:h.value==="catatan"}]),onClick:t[2]||(t[2]=s=>ot("catatan"))},[t[23]||(t[23]=a("span",{class:"sec-icon","aria-hidden":"true"},[a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M4 7h16M4 12h10M4 17h7",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",ja,[t[22]||(t[22]=a("span",{class:"sec-label"},"Catatan",-1)),a("span",Ma,u(x.value?"Prestasi siswa":k.value?"Pelanggaran siswa":"Pelanggaran, prestasi, konseling"),1)])],2)]),a("div",Ha,[a("p",Va,u(fe.value),1),h.value==="catatan"&&(L.value||k.value||x.value)?(o(),l("div",Ja,[x.value?f("",!0):(o(),l("button",{key:0,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="violations"}]),"aria-selected":P.value==="violations",onClick:t[3]||(t[3]=s=>P.value="violations")}," Pelanggaran ("+u(((i=g.value)==null?void 0:i.total)??0)+") ",11,za)),k.value?f("",!0):(o(),l("button",{key:1,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="achievements"}]),"aria-selected":P.value==="achievements",onClick:t[4]||(t[4]=s=>P.value="achievements")}," Prestasi ("+u(((r=g.value)==null?void 0:r.achievements_total)??((v=(c=g.value)==null?void 0:c.achievements)==null?void 0:v.length)??0)+") ",11,Ea)),L.value?(o(),l("button",{key:2,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="counseling"}]),"aria-selected":P.value==="counseling",onClick:t[5]||(t[5]=s=>P.value="counseling")}," Konseling ("+u(((d=g.value)==null?void 0:d.counseling_total)??((b=($=g.value)==null?void 0:$.counseling)==null?void 0:b.length)??0)+") ",11,Ia)):f("",!0)])):f("",!0),a("div",Fa,[tt(a("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>p.value.academic_year_id=s),class:"filter-select",onChange:Ye},[t[24]||(t[24]=a("option",{value:""},"Semua Tahun Ajaran",-1)),(o(!0),l(S,null,B(Rt.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Oa))),128))],544),[[it,p.value.academic_year_id]]),tt(a("select",{"onUpdate:modelValue":t[7]||(t[7]=s=>p.value.semester_id=s),class:"filter-select",onChange:nt},[t[25]||(t[25]=a("option",{value:""},"Semua Semester",-1)),(o(!0),l(S,null,B(Nt.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Ua))),128))],544),[[it,p.value.semester_id]]),tt(a("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>p.value.class_id=s),class:"filter-select",onChange:nt,disabled:Y.value&&U.value.length<=1},[Y.value?f("",!0):(o(),l("option",qa,"Semua Kelas")),(o(!0),l(S,null,B(U.value,s=>(o(),l("option",{key:s.id,value:String(s.id)},u(s.name),9,Ya))),128))],40,Ga),[[it,p.value.class_id]]),a("button",{type:"button",class:"filter-toggle","aria-expanded":E.value,onClick:t[9]||(t[9]=s=>E.value=!E.value)},[et(u(E.value?"Sembunyikan filter":"Filter lanjutan")+" ",1),a("span",Wa,u(E.value?"▼":"▶"),1)],8,Za)]),tt(a("div",Qa,[tt(a("select",{"onUpdate:modelValue":t[10]||(t[10]=s=>p.value.month=s),class:"filter-select",onChange:nt},[t[26]||(t[26]=a("option",{value:""},"Semua Bulan",-1)),(o(),l(S,null,B(12,s=>a("option",{key:s,value:String(s)},u(Z(s)),9,Xa)),64))],544),[[it,p.value.month]]),tt(a("select",{"onUpdate:modelValue":t[11]||(t[11]=s=>p.value.year=s),class:"filter-select",onChange:nt,title:"Tahun kalender"},[(o(),l(S,null,B(Ut,s=>a("option",{key:s,value:String(s)},"Tahun "+u(s),9,ts)),64))],544),[[it,p.value.year]])],512),[[aa,E.value]]),Qt.value.length?(o(),l("div",es,[(o(!0),l(S,null,B(Qt.value,s=>(o(),l("button",{key:s.key,type:"button",class:"filter-chip",disabled:s.disabled,onClick:T=>Ne(s.key)},[et(u(s.label)+" ",1),s.disabled?f("",!0):(o(),l("span",ss,"×"))],8,as))),128))])):f("",!0),O.value?(o(),l("div",ns,[ie(ma,{type:"table",rows:8,columns:7,"cell-widths":["90px","140px","160px","100px","80px","80px","1fr"]})])):h.value==="ringkasan"&&m.value?(o(),l(S,{key:3},[a("div",is,[x.value?f("",!0):(o(),l("button",{key:0,type:"button",class:"summary-card card-warning",onClick:t[12]||(t[12]=s=>Mt("violations"))},[t[27]||(t[27]=a("span",{class:"summary-icon summary-icon-warning","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 9v4M12 17h.01",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"}),a("path",{d:"M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z",stroke:"currentColor","stroke-width":"2","stroke-linejoin":"round"})])],-1)),a("span",ls,[a("span",os,u(((D=m.value.summary)==null?void 0:D.total_violations)??0),1),a("span",rs,"Pelanggaran · "+u(((K=m.value.summary)==null?void 0:K.total_violation_points)??0)+" poin",1)])])),k.value?f("",!0):(o(),l("button",{key:1,type:"button",class:"summary-card card-good",onClick:t[13]||(t[13]=s=>Mt("achievements"))},[t[28]||(t[28]=a("span",{class:"summary-icon summary-icon-good","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 2l2.4 6.9H22l-5.6 4.1 2.1 6.9L12 16.8 5.5 19.9 7.6 13 2 8.9h7.6L12 2z",stroke:"currentColor","stroke-width":"1.6","stroke-linejoin":"round"})])],-1)),a("span",us,[a("span",ds,u(((j=m.value.summary)==null?void 0:j.total_achievements)??0),1),a("span",cs,u(A.value?"Apresiasi":"Prestasi")+" · −"+u(((V=m.value.summary)==null?void 0:V.total_achievement_points)??0)+" poin",1)])])),L.value?(o(),l("button",{key:2,type:"button",class:"summary-card card-score",onClick:t[14]||(t[14]=s=>ot("skor"))},[t[30]||(t[30]=a("span",{class:"summary-icon summary-icon-score","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("circle",{cx:"12",cy:"12",r:"9",stroke:"currentColor","stroke-width":"2"}),a("path",{d:"M12 7v5l3 2",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",ps,[a("span",vs,u(((J=m.value.summary)==null?void 0:J.net_score)??0),1),t[29]||(t[29]=a("span",{class:"summary-label"},"Skor bersih",-1))])])):f("",!0),L.value?(o(),l("button",{key:3,type:"button",class:"summary-card card-total",onClick:t[15]||(t[15]=s=>Mt("counseling"))},[t[32]||(t[32]=a("span",{class:"summary-icon summary-icon-total","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("span",hs,[a("span",ms,u(((C=m.value.summary)==null?void 0:C.total_counseling)??0),1),t[31]||(t[31]=a("span",{class:"summary-label"},"Sesi konseling",-1))])])):f("",!0)]),a("section",gs,[t[37]||(t[37]=a("h3",{class:"section-title"},"Per kelas",-1)),(w=m.value.by_class)!=null&&w.length?(o(),l("div",fs,[a("table",ks,[a("thead",null,[a("tr",null,[t[35]||(t[35]=a("th",null,"Kelas",-1)),x.value?f("",!0):(o(),l("th",ys,"Pelanggaran")),k.value?f("",!0):(o(),l("th",$s,"Prestasi")),L.value?(o(),l("th",xs,"Konseling")):f("",!0),t[36]||(t[36]=a("th",null,null,-1))])]),a("tbody",null,[(o(!0),l(S,null,B(m.value.by_class,s=>(o(),l("tr",{key:s.class_id??s.class_name,class:"row-clickable",onClick:T=>ae(s)},[a("td",null,u(s.class_name),1),x.value?f("",!0):(o(),l("td",ws,u(s.violation_count),1)),k.value?f("",!0):(o(),l("td",Cs,u(s.achievement_count??0),1)),L.value?(o(),l("td",Ps,u(s.counseling_count),1)):f("",!0),a("td",Ts,[a("button",{type:"button",class:"link-btn",onClick:sa(T=>ae(s),["stop"])},u(x.value?"Lihat prestasi →":(k.value,"Skor siswa →")),9,Ls)])],8,Ss))),128))])])])):(o(),l("div",_s,[t[33]||(t[33]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),t[34]||(t[34]=a("h3",{class:"empty-title"},"Belum ada data",-1)),a("p",bs,u(A.value?"Tidak ada apresiasi untuk filter yang dipilih.":x.value?"Tidak ada prestasi untuk filter yang dipilih.":k.value?"Tidak ada pelanggaran untuk filter yang dipilih.":"Tidak ada pelanggaran, prestasi, atau konseling untuk filter yang dipilih."),1)]))]),a("section",As,[a("div",Bs,[a("h3",Ds,"Tren "+u((M=m.value.by_month)==null?void 0:M.year),1),tt(a("select",{"onUpdate:modelValue":t[16]||(t[16]=s=>p.value.year=s),class:"filter-select filter-select-sm",onChange:Re,title:"Tahun kalender untuk grafik tren"},[(o(),l(S,null,B(Ut,s=>a("option",{key:"t"+s,value:String(s)},u(s),9,Rs)),64))],544),[[it,p.value.year]])]),ee.value?(o(),l("div",Ns,[a("div",Ks,[ie(na(ha),{data:ee.value,options:Be},null,8,["data"])])])):f("",!0)]),a("div",js,[x.value?f("",!0):(o(),l("section",Ms,[t[39]||(t[39]=a("h3",{class:"section-title"},"Jenis pelanggaran",-1)),(_=m.value.top_violation_types)!=null&&_.length?(o(),l("div",Hs,[a("table",Vs,[t[38]||(t[38]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(m.value.top_violation_types,s=>(o(),l("tr",{key:s.violation_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Js,u(s.count),1),a("td",zs,u(s.total_points),1)]))),128))])])])):(o(),l("p",Es,"Belum ada catatan pelanggaran."))])),k.value?f("",!0):(o(),l("section",Is,[t[41]||(t[41]=a("h3",{class:"section-title"},"Jenis prestasi",-1)),(ft=m.value.top_achievement_types)!=null&&ft.length?(o(),l("div",Fs,[a("table",Os,[t[40]||(t[40]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(m.value.top_achievement_types,s=>(o(),l("tr",{key:s.achievement_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Us,u(s.count),1),a("td",Gs,u(s.total_points),1)]))),128))])])])):(o(),l("p",qs,"Belum ada catatan prestasi."))]))])],64)):h.value==="skor"?(o(),l(S,{key:4},[(yt=(kt=g.value)==null?void 0:kt.by_student)!=null&&yt.length?(o(),l("div",Zs,[a("table",Ws,[t[43]||(t[43]=a("thead",null,[a("tr",null,[a("th",null,"#"),a("th",null,"NIS"),a("th",null,"Nama"),a("th",{class:"th-num"},"Pelanggaran"),a("th",{class:"th-num"},"Prestasi"),a("th",{class:"th-num"},"Skor")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(Wt.value,s=>(o(),l(S,{key:"bk-"+s.key},[a("tr",Qs,[a("td",Xs,"Kelas "+u(s.name)+" · "+u(s.rows.length)+" siswa",1)]),(o(!0),l(S,null,B(s.rows,(T,Tt)=>(o(),l("tr",{key:T.student_id??s.key+"-"+Tt},[a("td",null,u(s.start+Tt+1),1),a("td",null,u(T.nis||"—"),1),a("td",null,u(T.student_name||"—"),1),a("td",tn,[et(u(T.violation_points??T.total_points)+" ",1),a("span",en,"("+u(T.violation_count)+")",1)]),a("td",an,[et("−"+u(T.achievement_points??0)+" ",1),a("span",sn,"("+u(T.achievement_count??0)+")",1)]),a("td",{class:z(["td-num td-total",$e(T.score)])},u(T.score??(T.violation_points??T.total_points)-(T.achievement_points??0)),3)]))),128))],64))),128))])])])):(o(),l("div",Ys,[...t[42]||(t[42]=[X('<div class="empty-icon" data-v-2eb0fca4><svg width="80" height="80" aria-hidden="true" data-v-2eb0fca4><use href="#bk-empty" data-v-2eb0fca4></use></svg></div><h3 class="empty-title" data-v-2eb0fca4>Belum ada data</h3><p class="empty-desc" data-v-2eb0fca4>Tidak ada siswa dengan pelanggaran atau prestasi untuk filter ini.</p>',3)])]))],64)):h.value==="catatan"?(o(),l(S,{key:5},[($t=g.value)!=null&&$t.truncated?(o(),l("p",nn,"Ditampilkan maksimal 2000 baris per jenis catatan.")):f("",!0),P.value==="violations"?(o(),l("section",ln,[(St=(xt=g.value)==null?void 0:xt.items)!=null&&St.length?(o(),l("div",rn,[a("table",un,[t[45]||(t[45]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",{class:"th-num"},"Poin"),a("th",null,"Status")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(g.value.items,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.violation_date)),1),a("td",null,[a("span",dn,u(s.student_name||"—"),1),a("span",cn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.violation_type),1),a("td",pn,u(s.point_weight),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(qt(s.status)),3)])]))),128))])])])):(o(),l("div",on,[...t[44]||(t[44]=[X('<div class="empty-icon" data-v-2eb0fca4><svg width="80" height="80" aria-hidden="true" data-v-2eb0fca4><use href="#bk-empty" data-v-2eb0fca4></use></svg></div><h3 class="empty-title" data-v-2eb0fca4>Belum ada pelanggaran</h3><p class="empty-desc" data-v-2eb0fca4>Tidak ada catatan untuk filter yang dipilih.</p>',3)])]))])):P.value==="achievements"?(o(),l("section",vn,[(rt=(wt=g.value)==null?void 0:wt.achievements)!=null&&rt.length?(o(),l("div",_n,[a("table",bn,[a("thead",null,[a("tr",null,[t[47]||(t[47]=a("th",null,"Tanggal",-1)),t[48]||(t[48]=a("th",null,"Siswa",-1)),t[49]||(t[49]=a("th",null,"Kelas",-1)),a("th",null,u(A.value?"Uraian":"Lomba / Kegiatan"),1),t[50]||(t[50]=a("th",null,"Jenis",-1)),At.value?(o(),l("th",fn,"Tingkat")):f("",!0),t[51]||(t[51]=a("th",{class:"th-num"},"Poin",-1))])]),a("tbody",null,[(o(!0),l(S,null,B(g.value.achievements,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.achievement_date)),1),a("td",null,[a("span",kn,u(s.student_name||"—"),1),a("span",yn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.title||s.notes||"—"),1),a("td",null,u(s.achievement_type),1),At.value?(o(),l("td",$n,[(s.purpose||"akreditasi")==="akreditasi"?(o(),l(S,{key:0},[et(u(Bt(s.level)),1),s.rank?(o(),l(S,{key:0},[et(" · "+u(Dt(s.rank)),1)],64)):f("",!0)],64)):(o(),l(S,{key:1},[et("—")],64))])):f("",!0),a("td",xn,"−"+u(s.point_value),1)]))),128))])])])):(o(),l("div",hn,[t[46]||(t[46]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),a("h3",mn,"Belum ada "+u(A.value?"apresiasi":"prestasi"),1),a("p",gn,"Tidak ada catatan "+u(A.value?"apresiasi":"prestasi")+" untuk filter yang dipilih.",1)]))])):(o(),l("section",Sn,[(Pt=(Ct=g.value)==null?void 0:Ct.counseling)!=null&&Pt.length?(o(),l("div",Cn,[a("table",Pn,[t[53]||(t[53]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",null,"Status"),a("th",null,"Konselor")])],-1)),a("tbody",null,[(o(!0),l(S,null,B(g.value.counseling,s=>(o(),l("tr",{key:s.id},[a("td",null,u(W(s.session_date)),1),a("td",null,[a("span",Tn,u(s.student_name||"—"),1),a("span",Ln,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.counseling_type),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(Yt(s.status)),3)]),a("td",null,u(s.counselor_name||"—"),1)]))),128))])])])):(o(),l("div",wn,[...t[52]||(t[52]=[X('<div class="empty-icon" data-v-2eb0fca4><svg width="80" height="80" aria-hidden="true" data-v-2eb0fca4><use href="#bk-empty" data-v-2eb0fca4></use></svg></div><h3 class="empty-title" data-v-2eb0fca4>Belum ada konseling</h3><p class="empty-desc" data-v-2eb0fca4>Tidak ada sesi konseling untuk filter yang dipilih.</p>',3)])]))]))],64)):O.value?f("",!0):(o(),l("div",An,[...t[54]||(t[54]=[X('<div class="empty-icon" data-v-2eb0fca4><svg width="80" height="80" aria-hidden="true" data-v-2eb0fca4><use href="#bk-empty" data-v-2eb0fca4></use></svg></div><h3 class="empty-title" data-v-2eb0fca4>Gagal memuat laporan</h3><p class="empty-desc" data-v-2eb0fca4>Coba ubah filter atau periksa koneksi.</p>',3)])]))])])])}}},Un=ga(Bn,[["__scopeId","data-v-2eb0fca4"]]);export{Un as default};
