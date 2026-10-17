import{l as y,r as N,o as oa,w as le,D as o,B as r,G as a,H as b,Y as et,I as u,K as S,E as B,F as z,P as at,$ as lt,R as st,Z as ra,O as oe,J as ua,x as da}from"./vue-CqAC-Kyw.js";import{b as ca,u as pa}from"./vue-router-jeJHNYHJ.js";import{C as va,a as ha,L as ma,b as ga,p as _a,e as ka,f as ba,g as fa}from"./chart-DIYX5MHI.js";import{L as ya}from"./LoadingSkeleton-Dnhi5lsR.js";import{m as At,_ as $a,u as xa,h as Sa,a as wa,w as Ca,s as Pa}from"./index-CpH1hCdp.js";import{i as re}from"./institution-Crr1dcgO.js";import{c as Ta}from"./class-DVhOEauZ.js";import{s as La}from"./semester-CM_LtM3e.js";import{u as Aa}from"./referenceData-x8OZMQ1d.js";import{f as Ba}from"./printFooter-PsOOoVNP.js";import"./pinia-Bf7Le6s2.js";import"./axios-C0Zqfgkc.js";import"./academicYear-Blk2ARok.js";const ct={getSummary(I){return At.get("/v1/bk-reports/summary",{params:I})},getViolationDetail(I){return At.get("/v1/bk-reports/violations",{params:I})},export(I){return At.get("/v1/bk-reports/export",{params:I,responseType:"blob"})},exportViolations(I){return At.get("/v1/bk-reports/export-violations",{params:I,responseType:"blob"})}},Ra={class:"laporan-bk-page"},Da={class:"toolbar"},Na={class:"header-actions toolbar-actions"},Ka=["disabled"],Ma=["disabled"],ja={key:0,class:"report-kind-nav","aria-label":"Jenis laporan"},Ha=["onClick"],Va={class:"tab-shell"},Ja={class:"section-nav","aria-label":"Navigasi laporan BK"},za={class:"sec-text"},Ea={class:"sec-hint"},Ia={class:"tab-main"},Fa={class:"tab-description"},Oa={key:0,class:"sub-nav",role:"tablist","aria-label":"Jenis catatan"},Ua=["aria-selected"],Ga=["aria-selected"],qa=["aria-selected"],Ya={class:"filters"},Za=["value"],Wa=["value"],Qa=["disabled"],Xa={key:0,value:""},ts=["value"],es=["aria-expanded"],as={class:"filter-toggle-icon"},ss={class:"filters filters-advanced"},ns=["value"],is=["value"],ls={key:1,class:"filter-chips"},os=["disabled","onClick"],rs={key:0,"aria-hidden":"true"},us={key:2,class:"loading-wrap"},ds={class:"stat-cards"},cs={class:"summary-body"},ps={class:"summary-value"},vs={class:"summary-label"},hs={class:"summary-body"},ms={class:"summary-value"},gs={class:"summary-label"},_s={class:"summary-body"},ks={class:"summary-value"},bs={class:"summary-body"},fs={class:"summary-value"},ys={class:"report-section"},$s={key:0,class:"empty-state"},xs={class:"empty-desc"},Ss={key:1,class:"table-container"},ws={class:"data-table"},Cs={key:0,class:"th-num"},Ps={key:1,class:"th-num"},Ts={key:2,class:"th-num"},Ls=["onClick"],As={key:0,class:"td-num"},Bs={key:1,class:"td-num td-good"},Rs={key:2,class:"td-num"},Ds={class:"td-action"},Ns=["onClick"],Ks={class:"report-section"},Ms={class:"section-head"},js={class:"section-title"},Hs=["value"],Vs={key:0,class:"chart-box"},Js={class:"chart-wrap"},zs={class:"split-tables"},Es={key:0,class:"report-section"},Is={key:0,class:"table-container"},Fs={class:"data-table"},Os={class:"td-num"},Us={class:"td-num"},Gs={key:1,class:"empty-desc muted"},qs={key:1,class:"report-section"},Ys={key:0,class:"table-container"},Zs={class:"data-table"},Ws={class:"td-num"},Qs={class:"td-num td-good"},Xs={key:1,class:"empty-desc muted"},tn={key:0,class:"empty-state"},en={key:1,class:"table-container"},an={class:"data-table"},sn={class:"group-row"},nn={colspan:"6"},ln={class:"td-num"},on={class:"td-muted"},rn={class:"td-num td-good"},un={class:"td-muted"},dn={key:0,class:"section-hint"},cn={key:1,class:"report-section"},pn={key:0,class:"empty-state"},vn={key:1,class:"table-container"},hn={class:"data-table"},mn={class:"student-name"},gn={class:"student-meta"},_n={class:"td-num"},kn={key:2,class:"report-section"},bn={key:0,class:"empty-state"},fn={class:"empty-title"},yn={class:"empty-desc"},$n={key:1,class:"table-container"},xn={class:"data-table"},Sn={key:0},wn={class:"student-name"},Cn={class:"student-meta"},Pn={key:0},Tn={class:"td-num td-good"},Ln={key:3,class:"report-section"},An={key:0,class:"empty-state"},Bn={key:1,class:"table-container"},Rn={class:"data-table"},Dn={class:"student-name"},Nn={class:"student-meta"},Kn={key:6,class:"empty-state"},Mn={__name:"CounselingReport",setup(I){va.register(ha,ma,ga,_a,ka,ba);const F=wa(),H=ca(),ue=pa(),Vt=Aa(),pt=xa(),de=y(()=>Sa(pt.user,"violation")),ce=[{to:"/bk/laporan",label:"Gabungan",scope:"combined"},{to:"/bk/laporan/pelanggaran",label:"Pelanggaran",scope:"violations",requiresViolation:!0},{to:"/bk/laporan/prestasi",label:"Prestasi",scope:"achievements",purpose:"akreditasi",requiresViolation:!0},{to:"/bk/laporan/apresiasi",label:"Apresiasi",scope:"achievements",purpose:"apresiasi",requiresViolation:!0}],Jt=y(()=>ce.filter(e=>!e.requiresViolation||de.value)),vt=y(()=>H.meta.reportScope||"combined"),ht=y(()=>H.meta.reportPurpose||null),L=y(()=>vt.value==="combined"),f=y(()=>vt.value==="violations"),x=y(()=>vt.value==="achievements"),A=y(()=>ht.value==="apresiasi"),D=y(()=>ht.value==="akreditasi"),Bt=y(()=>!A.value);function pe(e){return e.scope==="combined"?L.value:e.scope==="violations"?f.value:e.purpose==="apresiasi"?A.value:e.purpose==="akreditasi"?D.value:H.path===e.to}function ve(e){H.path!==e&&ue.push(e)}const zt=y(()=>A.value?"Laporan Apresiasi":D.value?"Laporan Prestasi":f.value?"Laporan Pelanggaran":"Laporan BK"),Et=y(()=>A.value?"Laporan_Apresiasi":D.value?"Laporan_Prestasi":f.value?"Laporan_Pelanggaran":"Laporan_BK"),he={sekolah:"Sekolah",kabupaten:"Kabupaten/Kota",provinsi:"Provinsi",nasional:"Nasional",internasional:"Internasional"},me={juara_1:"Juara 1",juara_2:"Juara 2",juara_3:"Juara 3",finalis:"Finalis",peserta:"Peserta",lainnya:"Lainnya"};function Rt(e){return he[e]||e||"—"}function Dt(e){return me[e]||e||""}const ge={akademik:"Akademik",non_akademik:"Non Akademik",sikap:"Sikap"},It={internasional:1,nasional:2,provinsi:3,kabupaten:4,sekolah:5},_e={ringan:"Ringan",sedang:"Sedang",berat:"Berat",sangat_berat:"Sangat Berat"};function ke(e){return ge[e]||e||"—"}function Ft(e){return _e[e]||e||"—"}function be(e){return[...e||[]].sort((t,i)=>{const l=(t.class_name||"").localeCompare(i.class_name||"");if(l!==0)return l;const c=It[t.level]??6,v=It[i.level]??6;return c!==v?c-v:(i.achievement_date||"").localeCompare(t.achievement_date||"")})}function fe(e){return Wt(be(e))}function Ot(e){const t=e||[],i=new Set,l={},c={},v={};for(const d of t){d.student_id&&i.add(d.student_id);const $=d.level||"_kosong";l[$]=(l[$]||0)+1;const k=d.category||"_lainnya";c[k]=(c[k]||0)+1,d.rank&&(v[d.rank]=(v[d.rank]||0)+1)}return{total:t.length,students:i.size,byLevel:l,byCategory:c,byRank:v}}function ye(e){return!e||e==="_kosong"?"Belum diisi":Rt(e)}const O=N(!0),mt=N(!1),gt=N(!1),h=N("ringkasan"),P=N("violations"),E=N(!1),m=N(null),g=N(null),Y=N({principal:{},bk:{}}),U=N([]),_t=N([]),G=N(null),Z=y(()=>{var e;return((e=pt.user)==null?void 0:e.bk_scope)==="homeroom"}),Ut=y(()=>{var e;return(((e=pt.user)==null?void 0:e.homeroom_class_ids)||[]).map(Number)}),q=y(()=>h.value!=="ringkasan"),$e=y(()=>Z.value?"Menampilkan siswa di kelas yang Anda walikan saja.":x.value?A.value?h.value==="ringkasan"?"Rekap apresiasi dan laporan positif siswa per kelas.":"Daftar apresiasi / laporan siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap prestasi lomba/kompetisi (akreditasi) per kelas, kategori, dan jenis.":"Daftar prestasi akreditasi sesuai filter yang dipilih.":f.value?h.value==="ringkasan"?"Rekap pelanggaran siswa per kelas dan jenis.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi.":"Daftar pelanggaran siswa sesuai filter yang dipilih.":h.value==="ringkasan"?"Rekap kelas. Skor bersih = poin pelanggaran − poin prestasi.":h.value==="skor"?"Skor per siswa = poin pelanggaran − poin prestasi. Contoh: 40 − 20 = 20.":"Catatan pelanggaran, prestasi, dan konseling sesuai filter yang dipilih."),kt=new Date().getFullYear(),Gt=[kt,kt-1,kt-2],p=N({academic_year_id:"",semester_id:"",class_id:"",month:"",year:String(kt)}),Nt=y(()=>Vt.academicYears||[]),Kt=y(()=>{const e=p.value.academic_year_id;return e?_t.value.filter(t=>String(t.academic_year_id)===String(e)):_t.value}),qt=["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"],xe={dicatat:"Dicatat",sanksi_diberikan:"Sanksi Diberikan",follow_up:"Follow Up",selesai:"Selesai"},Se={jadwal:"Jadwal",berlangsung:"Berlangsung",selesai:"Selesai",dibatalkan:"Dibatalkan"};function W(e){return qt[e]||`Bulan ${e}`}function Yt(e){return xe[e]||e||"—"}function Zt(e){return Se[e]||e||"—"}function we(e){const t=Number(e);return Number.isNaN(t)?"":t>40?"score-bad":t>20?"score-warn":t<=0?"score-good":""}function Q(e){if(!e)return"—";const[t,i,l]=e.split("-");return!t||!i||!l?e:`${l}/${i}/${t}`}function Wt(e){const t=[];let i=null,l=0;for(const c of e||[]){const v=c.class_name||"Tanpa kelas";(!i||i.name!==v)&&(i={key:v,name:v,rows:[],start:l},t.push(i)),i.rows.push(c),l+=1}return t}const Qt=y(()=>{var e;return Wt(((e=g.value)==null?void 0:e.by_student)||[])}),ot=y(()=>{const e=U.value.find(t=>String(t.id)===String(p.value.class_id));return(e==null?void 0:e.name)||""}),Xt=y(()=>{const e=[];return p.value.class_id&&ot.value&&e.push({key:"class",label:`Kelas ${ot.value}`,disabled:Z.value&&U.value.length<=1}),E.value&&p.value.month&&e.push({key:"month",label:`${W(Number(p.value.month))} ${p.value.year}`,disabled:!1}),e}),Ce=y(()=>{var l,c;const e=[];e.push(ot.value||"Semua kelas");const t=(l=Nt.value.find(v=>String(v.id)===String(p.value.academic_year_id)))==null?void 0:l.name;t&&e.push(t);const i=(c=Kt.value.find(v=>String(v.id)===String(p.value.semester_id)))==null?void 0:c.name;return i&&e.push(i),E.value&&p.value.month&&e.push(`${W(Number(p.value.month))} ${p.value.year}`),e.join(" · ")}),te=y(()=>{const e=p.value.academic_year_id;return e&&Nt.value.find(t=>String(t.id)===String(e))||null}),Pe=y(()=>{var e;return((e=te.value)==null?void 0:e.name)||"—"});function Te(e){if(e!=null&&e.start_date&&(e!=null&&e.end_date)){const v=new Date(e.start_date),d=new Date(e.end_date);return{startYear:v.getFullYear(),endYear:d.getFullYear()}}const t=String((e==null?void 0:e.name)||"").match(/(\d{4})\s*\/\s*(\d{4})/);if(t)return{startYear:parseInt(t[1],10),endYear:parseInt(t[2],10)};const i=new Date,l=i.getFullYear(),c=i.getMonth()+1>=7?l:l-1;return{startYear:c,endYear:c+1}}function Le(e,t){const{startYear:i,endYear:l}=Te(t),c={};for(const d of e||[]){if(!d.achievement_date)continue;const[$,k]=d.achievement_date.split("-").map(Number);if(!$||!k)continue;const R=`${$}-${k}`;c[R]=(c[R]||0)+1}const v=[];for(let d=0;d<12;d++){const $=(6+d)%12+1,k=$>=7?i:l;v.push({month:$,year:k,label:`${W($)} ${k}`,count:c[`${k}-${$}`]||0})}return{slots:v,rangeLabel:`${W(7)} ${i} – ${W(6)} ${l}`}}function Ae(e){if(!(e!=null&&e.length))return'<tr><td colspan="9">Belum ada data prestasi untuk filter yang dipilih.</td></tr>';const t=fe(e),i=9;return t.flatMap(l=>{const c=`<tr class="group-row"><td colspan="${i}">Kelas ${n(l.name)} · ${l.rows.length} prestasi</td></tr>`,v=l.rows.map((d,$)=>{const k=[d.nis,d.nisn].filter(Boolean).join(" / ")||"—",R=d.notes&&d.title&&d.notes!==d.title?d.notes:d.notes||"—";return`
      <tr>
        <td class="center">${l.start+$+1}</td>
        <td>
          <strong>${n(d.student_name||"—")}</strong><br>
          <span class="cell-sub">${n(k)}</span>
        </td>
        <td>${n(d.title||d.notes||"—")}</td>
        <td>${n(ke(d.category))}</td>
        <td>${n(d.achievement_type||"—")}</td>
        <td>${n(ye(d.level))}</td>
        <td class="center">${n(d.rank?Dt(d.rank):"—")}</td>
        <td class="center nowrap">${n(Q(d.achievement_date))}</td>
        <td>${n(R)}</td>
      </tr>`}).join("");return c+v}).join("")}function ee(e,t="Data Prestasi"){return`
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
      <tbody>${Ae(e)}</tbody>
    </table>
  `}function Be(e,t="2. Tren Bulanan"){const{slots:i,rangeLabel:l}=Le(e,te.value),c=i.map(v=>`
    <tr>
      <td>${n(v.label)}</td>
      <td class="num">${v.count}</td>
    </tr>
  `).join("");return`
    <h2>${n(t)} <span class="h2-sub">(${n(l)})</span></h2>
    <table class="table-trend">
      <thead>
        <tr><th>Bulan</th><th class="num">Jumlah Prestasi</th></tr>
      </thead>
      <tbody>${c}</tbody>
    </table>
  `}function Re(){var e;if(D.value&&h.value==="ringkasan"){const t=[],i=(e=Kt.value.find(l=>String(l.id)===String(p.value.semester_id)))==null?void 0:e.name;return i&&t.push(i),ot.value&&t.push(`Kelas ${ot.value}`),t.length?`<div class="period"><strong>Filter tambahan:</strong> ${n(t.join(" · "))}</div>`:""}return`<div class="period"><strong>Periode / Filter:</strong> ${n(Ce.value)}</div>`}function De(){return D.value&&h.value==="ringkasan"?`<div class="subtitle academic-year">Tahun Ajaran ${n(Pe.value)}</div>`:D.value?'<div class="subtitle">Prestasi Lomba & Kompetisi · Bimbingan Konseling</div>':`<div class="subtitle">${n(zt.value)} · Bimbingan Konseling</div>`}function bt({forDetail:e=!1}={}){const t={academic_year_id:p.value.academic_year_id,semester_id:p.value.semester_id,class_id:p.value.class_id,year:p.value.year},i=E.value&&!!p.value.month;return e?i?t.month=p.value.month:delete t.year:i&&(t.month=p.value.month),Object.keys(t).forEach(l=>{l==="academic_year_id"||l==="semester_id"||(t[l]===""||t[l]===null||t[l]===void 0)&&delete t[l]}),ht.value&&(t.purpose=ht.value),t.report_scope=vt.value,t}const ae=y(()=>{var i,l;const e=(l=(i=m.value)==null?void 0:i.by_month)==null?void 0:l.months;if(!(e!=null&&e.length))return null;const t=[];return x.value||t.push({label:"Pelanggaran",data:e.map(c=>c.violation_count),backgroundColor:"rgba(239, 68, 68, 0.7)",borderRadius:4}),f.value||t.push({label:"Prestasi",data:e.map(c=>c.achievement_count??0),backgroundColor:"rgba(16, 185, 129, 0.7)",borderRadius:4}),L.value&&t.push({label:"Konseling",data:e.map(c=>c.counseling_count),backgroundColor:"rgba(14, 165, 233, 0.7)",borderRadius:4}),t.length?{labels:e.map(c=>c.label||qt[c.month]),datasets:t}:null}),Ne={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom"}},scales:{y:{beginAtZero:!0,ticks:{precision:0}}}};function Mt(e){e!=null&&e.signers&&(Y.value=e.signers)}async function nt(){var e;O.value=!0;try{const t=await ct.getSummary(bt({forDetail:!1}));m.value=((e=t.data)==null?void 0:e.data)??null,Mt(m.value)}catch(t){m.value=null,F.error("Gagal memuat ringkasan",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}async function Ke(){var e;if(!(g.value&&Array.isArray(g.value.achievements)))try{const t=await ct.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Mt(g.value)}catch{}}async function X(){var e;O.value=!0;try{const t=await ct.getViolationDetail(bt({forDetail:!0}));g.value=((e=t.data)==null?void 0:e.data)??null,Mt(g.value)}catch(t){g.value=null,F.error("Gagal memuat detail",t.formattedMessage||"Periksa koneksi dan coba lagi.")}finally{O.value=!1}}function jt(){m.value=null,g.value=null}async function it(){jt(),q.value?await X():await nt()}async function Me(){m.value=null,await nt()}async function rt(e){h.value!==e&&(h.value=e,e==="ringkasan"?m.value||await nt():g.value||await X())}async function Ht(e){P.value=e,await rt("catatan")}async function se(e){if(e.class_id&&(p.value.class_id=String(e.class_id)),m.value=null,g.value=null,x.value){P.value="achievements",h.value="catatan",await X();return}h.value="skor",await X()}async function je(e){if(e==="class"){if(Z.value&&U.value.length<=1)return;p.value.class_id=""}e==="month"&&(p.value.month=""),await it()}async function He(){mt.value=!0;try{const e=q.value,t=bt({forDetail:e});e&&(t.export_view=h.value,L.value&&h.value==="catatan"&&(t.notes_kind=P.value));const i=e?await ct.exportViolations(t):await ct.export(t),l=window.URL.createObjectURL(new Blob([i.data])),c=document.createElement("a");c.href=l;const v=Et.value.replace(/_/g,"-").toLowerCase();c.setAttribute("download",e?`${v}-detail-${new Date().toISOString().slice(0,10)}.csv`:`${v}-per-kelas-${new Date().toISOString().slice(0,10)}.csv`),document.body.appendChild(c),c.click(),c.remove(),window.URL.revokeObjectURL(l),F.success("Export berhasil diunduh")}catch(e){F.error("Gagal mengekspor",e.formattedMessage||"Data tidak dapat diekspor.")}finally{mt.value=!1}}function n(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function Ve(){if(!g.value)return"<p>Tidak ada data.</p>";const e=!f.value,t=e?9:7,i=(Qt.value||[]).flatMap(v=>{const d=`<tr class="group-row"><td colspan="${t}">Kelas ${n(v.name)} (${v.rows.length} siswa)</td></tr>`,$=v.rows.map((k,R)=>{const K=k.violation_points??k.total_points??0,M=k.achievement_points??0,V=k.score??K-M,J=e?`<td class="num">${n(k.achievement_count??0)}</td>
        <td class="num">−${n(M)}</td>`:"";return`
      <tr>
        <td>${v.start+R+1}</td>
        <td>${n(k.nis||"—")}</td>
        <td>${n(k.student_name||"—")}</td>
        <td>${n(k.class_name)}</td>
        <td class="num">${n(k.violation_count)}</td>
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
  `}function Je(){var t;return`
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
      <td>${n(Q(i.violation_date))}</td>
      <td>${n(i.nis||"—")}</td>
      <td>${n(i.student_name||"—")}</td>
      <td>${n(i.class_name)}</td>
      <td>${n(i.violation_type)}</td>
      <td>${n(Ft(i.category))}</td>
      <td class="num">${n(i.point_weight)}</td>
      <td>${n(Yt(i.status))}</td>
      <td>${n(i.reporter_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="9">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function ze(){var v;if(D.value)return Ee();const e=Bt.value,t=A.value?"Uraian":"Lomba / Kegiatan",i=A.value?"Daftar Apresiasi":"Daftar Prestasi",l=e?"<th>Tingkat</th>":"",c=(((v=g.value)==null?void 0:v.achievements)||[]).map(d=>{const $=(d.purpose||"akreditasi")==="akreditasi",k=e?`<td>${$?`${n(Rt(d.level))}${d.rank?` · ${n(Dt(d.rank))}`:""}`:"—"}</td>`:"";return`
      <tr>
        <td>${n(Q(d.achievement_date))}</td>
        <td>${n(d.nis||"—")}</td>
        <td>${n(d.student_name||"—")}</td>
        <td>${n(d.class_name)}</td>
        <td>${n(d.title||d.notes||"—")}</td>
        <td>${n(d.achievement_type)}</td>
        ${k}
        <td class="num">−${n(d.point_value)}</td>
        <td>${n(d.giver_name||"—")}</td>
      </tr>`}).join("")||`<tr><td colspan="${e?9:8}">Belum ada data</td></tr>`;return`
    <h2>${i}</h2>
    <table>
      <thead>
        <tr>
          <th>Tanggal</th><th>NIS</th><th>Nama</th><th>Kelas</th>
          <th>${t}</th><th>Jenis</th>${l}<th>Poin</th><th>Pemberi</th>
        </tr>
      </thead>
      <tbody>${c}</tbody>
    </table>
  `}function Ee(){var i;const e=((i=g.value)==null?void 0:i.achievements)||[];if(!e.length)return"<p>Belum ada data prestasi untuk filter yang dipilih.</p>";const t=Ot(e);return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${t.total}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${t.students}</div></div>
    </div>
    ${ee(e,"Daftar Prestasi per Siswa")}
  `}function Ie(){var v,d;const e=((v=g.value)==null?void 0:v.achievements)||[],t=Ot(e),i=((d=m.value)==null?void 0:d.summary)||{},l=e.length?t.total:i.total_achievements??0,c=t.students;return`
    <div class="stats stats-akreditasi">
      <div class="stat"><div class="stat-label">Total Prestasi</div><div class="stat-value">${l}</div></div>
      <div class="stat"><div class="stat-label">Siswa Berprestasi</div><div class="stat-value">${c}</div></div>
    </div>
    ${ee(e,"1. Data Prestasi")}
    ${Be(e,"2. Tren Bulanan")}
  `}function Fe(){var t;return`
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
      <td>${n(Q(i.session_date))}</td>
      <td>${n(i.nis||"—")}</td>
      <td>${n(i.student_name||"—")}</td>
      <td>${n(i.class_name)}</td>
      <td>${n(i.counseling_type)}</td>
      <td>${n(Zt(i.status))}</td>
      <td>${n(i.counselor_name||"—")}</td>
    </tr>
  `).join("")||'<tr><td colspan="7">Belum ada data</td></tr>'}</tbody>
    </table>
  `}function Oe(){return f.value?"violations":x.value?"achievements":P.value}function Ue(){if(!g.value)return"<p>Tidak ada data.</p>";const e=Oe();return e==="violations"?Je():e==="achievements"?ze():Fe()}function Ge(){var k,R,K,M,V,J;if(D.value)return!m.value&&!((R=(k=g.value)==null?void 0:k.achievements)!=null&&R.length)?"<p>Tidak ada data.</p>":Ie();if(!m.value)return"<p>Tidak ada data.</p>";const e=m.value.summary||{},t=[];let i=1;const l=[];if(x.value||l.push(`<div class="stat"><div class="stat-label">Total Pelanggaran</div><div class="stat-value">${e.total_violations??0}</div></div>`),!f.value){const C=A.value?"Total Apresiasi":"Total Prestasi";l.push(`<div class="stat"><div class="stat-label">${C}</div><div class="stat-value">${e.total_achievements??0}</div></div>`)}L.value&&(l.push(`<div class="stat"><div class="stat-label">Skor Bersih</div><div class="stat-value">${e.net_score??0}</div></div>`),l.push(`<div class="stat"><div class="stat-label">Total Konseling</div><div class="stat-value">${e.total_counseling??0}</div></div>`)),l.length&&t.push(`<div class="stats">${l.join("")}</div>`),L.value&&t.push('<p class="note">Skor bersih = poin pelanggaran − poin prestasi.</p>');const c=["<th>Kelas</th>"];x.value||c.push("<th>Pelanggaran</th>"),f.value||c.push(`<th>${A.value?"Apresiasi":"Prestasi"}</th>`),L.value&&c.push("<th>Konseling</th>");const v=(m.value.by_class||[]).map(C=>{const w=[`<td>${n(C.class_name)}</td>`];return x.value||w.push(`<td class="num">${n(C.violation_count)}</td>`),f.value||w.push(`<td class="num">${n(C.achievement_count??0)}</td>`),L.value&&w.push(`<td class="num">${n(C.counseling_count)}</td>`),`<tr>${w.join("")}</tr>`}).join("")||`<tr><td colspan="${c.length}">Belum ada data</td></tr>`;t.push(`
    <h2>${i++}. Rekap per Kelas</h2>
    <table>
      <thead><tr>${c.join("")}</tr></thead>
      <tbody>${v}</tbody>
    </table>
  `);const d=["<th>Bulan</th>"];x.value||d.push("<th>Pelanggaran</th>"),f.value||d.push(`<th>${A.value?"Apresiasi":"Prestasi"}</th>`),L.value&&d.push("<th>Konseling</th>");const $=(((K=m.value.by_month)==null?void 0:K.months)||[]).map(C=>{const w=[`<td>${n(W(C.month))}</td>`];return x.value||w.push(`<td class="num">${n(C.violation_count)}</td>`),f.value||w.push(`<td class="num">${n(C.achievement_count??0)}</td>`),L.value&&w.push(`<td class="num">${n(C.counseling_count)}</td>`),`<tr>${w.join("")}</tr>`}).join("")||`<tr><td colspan="${d.length}">Belum ada data</td></tr>`;if(t.push(`
    <h2>${i++}. Rekap per Bulan (${n(((M=m.value.by_month)==null?void 0:M.year)||p.value.year)})</h2>
    <table>
      <thead><tr>${d.join("")}</tr></thead>
      <tbody>${$}</tbody>
    </table>
  `),!x.value&&((V=m.value.top_violation_types)!=null&&V.length)){const C=m.value.top_violation_types.map((w,j)=>`
      <tr>
        <td>${j+1}</td>
        <td>${n(w.type_name)}</td>
        <td>${n(Ft(w.category))}</td>
        <td class="num">${n(w.count)}</td>
        <td class="num">${n(w.total_points)}</td>
      </tr>
    `).join("");t.push(`
      <h2>${i++}. Top Jenis Pelanggaran</h2>
      <table>
        <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
        <tbody>${C}</tbody>
      </table>
    `)}if(!f.value&&((J=m.value.top_achievement_types)!=null&&J.length)){const C=m.value.top_achievement_types.map((j,_)=>`
      <tr>
        <td>${_+1}</td>
        <td>${n(j.type_name)}</td>
        <td>${n(j.category||"—")}</td>
        <td class="num">${n(j.count)}</td>
        <td class="num">${n(j.total_points)}</td>
      </tr>
    `).join(""),w=A.value?"Top Jenis Apresiasi":"Top Jenis Prestasi";t.push(`
      <h2>${i++}. ${w}</h2>
      <table>
        <thead><tr><th>#</th><th>Jenis</th><th>Kategori</th><th>Jumlah</th><th>Total Poin</th></tr></thead>
        <tbody>${C}</tbody>
      </table>
    `)}return t.join("")}function qe(){return h.value==="ringkasan"?Ge():h.value==="skor"?Ve():Ue()}function Ye(){return D.value||q.value?"landscape":"portrait"}function Ze(){if(D.value){if(h.value==="ringkasan")return"Rekapitulasi Prestasi Siswa";if(h.value==="catatan")return"Daftar Prestasi Siswa — Lomba & Kompetisi"}const e=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor Siswa":"Catatan";return`${zt.value} — ${e}`}async function We(){var t,i,l,c,v,d,$,k,R,K,M,V,J,C,w;if(!(q.value?!!g.value:!!m.value)&&!D.value){F.error("Gagal","Tidak ada data untuk dicetak");return}gt.value=!0;try{if(await ta(),D.value&&await Ke(),!(D.value?!!(m.value||(i=(t=g.value)==null?void 0:t.achievements)!=null&&i.length):q.value?!!g.value:!!m.value)){F.error("Gagal","Tidak ada data untuk dicetak");return}const _=G.value||{},ft=_.name||"Sekolah",yt=[_.address,_.village?`Desa/Kel. ${_.village}`:"",_.sub_district?`Kec. ${_.sub_district}`:"",_.district,_.province,_.postal_code].filter(Boolean).join(", "),$t=h.value==="ringkasan"?"Ringkasan":h.value==="skor"?"Skor_Siswa":"Catatan",xt=Ze(),St=Ye(),wt=Ba((l=pt.user)==null?void 0:l.name),Ct=`${_.district||_.city||"........................"}, ${new Date().toLocaleDateString("id-ID",{day:"numeric",month:"long",year:"numeric"})}`,ut=`${Et.value}_${$t}_${new Date().toISOString().slice(0,10)}.pdf`,Pt=((v=(c=Y.value)==null?void 0:c.principal)==null?void 0:v.role)||Ca(_.level),Tt=(($=(d=Y.value)==null?void 0:d.principal)==null?void 0:$.name)||_.principal_name||"",s=((R=(k=Y.value)==null?void 0:k.principal)==null?void 0:R.nip)||_.principal_nip||"",T=((M=(K=Y.value)==null?void 0:K.bk)==null?void 0:M.role)||"Guru Bimbingan Konseling",Lt=((J=(V=Y.value)==null?void 0:V.bk)==null?void 0:J.name)||"",sa=((w=(C=Y.value)==null?void 0:C.bk)==null?void 0:w.nip)||"",na=`<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>${n(ut)}</title>
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
      @page { size: A4 ${St}; margin: 10mm 12mm 14mm 10mm; }
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
        <div class="school-address">${n(yt||"-")}</div>
        <div class="school-info">
          NPSN: ${n(_.npsn||"-")}
          ${_.nss?` · ${Pa(_.level)}: ${n(_.nss)}`:""}
          ${_.phone?` · Telp: ${n(_.phone)}`:""}
          ${_.email?` · Email: ${n(_.email)}`:""}
          ${_.website?` · ${n(_.website)}`:""}
        </div>
      </div>
      <div></div>
    </div>
  </header>
  <h1>${n(xt)}</h1>
  ${De()}
  ${Re()}
  ${qe()}
  <div class="sig-wrap">
    <div class="sig-col">
      <div class="sig">
        <div class="sig-place">&nbsp;</div>
        <div class="sig-role">Mengetahui,<br>${n(Pt)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${n(Tt||"___________________")}</div>
        <div class="sig-nip">NIP. ${n(s||"___________________")}</div>
      </div>
    </div>
    <div class="sig-col sig-col-right">
      <div class="sig">
        <div class="sig-place">${n(Ct)}</div>
        <div class="sig-role">${n(T)}</div>
        <div class="sig-space"></div>
        <div class="sig-name">${n(Lt||"___________________")}</div>
        <div class="sig-nip">NIP. ${n(sa||"___________________")}</div>
      </div>
    </div>
  </div>
  <div class="printed-at">${n(wt)}</div>
</body>
</html>`,tt=window.open("","_blank");if(!tt){F.error("Gagal","Popup diblokir. Izinkan popup untuk mencetak.");return}tt.document.write(na),tt.document.close();const ia=Array.from(tt.document.images||[]),la=Promise.all(ia.map(dt=>dt.complete?Promise.resolve():new Promise(ie=>{dt.onload=()=>ie(),dt.onerror=()=>ie()})));await Promise.race([la,new Promise(dt=>setTimeout(dt,2500))]),tt.focus(),tt.print(),tt.document.title=ut}catch{F.error("Gagal","Gagal menyiapkan cetak PDF")}finally{gt.value=!1}}async function Qe(){p.value.semester_id="",await ne(),await it()}async function ne(){var e;try{const t={per_page:200};p.value.academic_year_id&&(t.academic_year_id=p.value.academic_year_id),p.value.semester_id&&(t.semester_id=p.value.semester_id);let l=((e=(await Ta.getAll(t)).data)==null?void 0:e.data)||[];if(Z.value&&Ut.value.length){const c=new Set(Ut.value);l=l.filter(v=>c.has(Number(v.id)))}U.value=l,Z.value&&(l.length===1?p.value.class_id=String(l[0].id):p.value.class_id&&!l.some(c=>String(c.id)===String(p.value.class_id))&&(p.value.class_id=l[0]?String(l[0].id):""))}catch{U.value=[]}}async function Xe(){var e;try{const t=await La.getAll({per_page:100});_t.value=((e=t.data)==null?void 0:e.data)||[]}catch{_t.value=[]}}async function ta(){var e,t;if(!((e=G.value)!=null&&e.name))try{const i=await re.getMy(),l=((t=i.data)==null?void 0:t.data)||i.data;l!=null&&l.name&&(G.value=l)}catch{}}async function ea(){var e,t,i;try{const l=await re.getMy();G.value=((e=l.data)==null?void 0:e.data)||l.data,(t=G.value)!=null&&t.active_academic_year_id&&(p.value.academic_year_id=String(G.value.active_academic_year_id)),(i=G.value)!=null&&i.active_semester_id&&(p.value.semester_id=String(G.value.active_semester_id))}catch{}}oa(async()=>{await Promise.all([ea(),Vt.getAcademicYears(),Xe()]),await ne();const e=H.query.class_id?String(H.query.class_id):"";e&&U.value.some(l=>String(l.id)===e)&&(p.value.class_id=e);const t=String(H.query.tab||"");t==="detail"||t==="skor"?h.value="skor":t==="catatan"?h.value="catatan":(x.value||f.value&&h.value==="catatan"&&!t)&&(h.value="ringkasan");const i=String(H.query.notes||"");["violations","achievements","counseling"].includes(i)?P.value=i:x.value?P.value="achievements":f.value&&(P.value="violations"),x.value&&h.value==="skor"&&(h.value="ringkasan"),q.value?await X():await nt()});function aa(){x.value?(P.value="achievements",h.value==="skor"&&(h.value="ringkasan")):f.value&&(P.value="violations")}return le(()=>H.path,async()=>{aa(),jt(),q.value?await X():await nt()}),le(()=>H.meta.reportPurpose,async()=>{jt(),q.value?await X():await nt()}),(e,t)=>{var i,l,c,v,d,$,k,R,K,M,V,J,C,w,j,_,ft,yt,$t,xt,St,wt,Ct,ut,Pt,Tt;return r(),o("div",Ra,[t[55]||(t[55]=a("svg",{xmlns:"http://www.w3.org/2000/svg",class:"icon-sprite","aria-hidden":"true"},[a("symbol",{id:"bk-empty",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H15M12 16H15M9 12H9.01M9 16H9.01",stroke:"currentColor","stroke-width":"1.5","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("div",Da,[t[19]||(t[19]=a("div",{class:"toolbar-spacer"},null,-1)),a("div",Na,[a("button",{type:"button",class:"btn-secondary btn-compact",disabled:gt.value||O.value,onClick:We},[t[17]||(t[17]=et('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-4c3d452c><path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-4c3d452c></path><path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-4c3d452c></path><path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-4c3d452c></path><path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-4c3d452c></path></svg>',1)),a("span",null,u(gt.value?"Menyiapkan...":"Cetak PDF"),1)],8,Ka),a("button",{type:"button",class:"btn-secondary btn-compact",disabled:mt.value||O.value,onClick:He},[t[18]||(t[18]=a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[a("path",{d:"M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M7 10L12 15L17 10",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),a("path",{d:"M12 15V3",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})],-1)),a("span",null,u(mt.value?"Mengekspor...":"Export CSV"),1)],8,Ma)])]),Jt.value.length>1?(r(),o("nav",ja,[(r(!0),o(S,null,B(Jt.value,s=>(r(),o("button",{key:s.to,type:"button",class:z(["sub-nav-btn",{active:pe(s)}]),onClick:T=>ve(s.to)},u(s.label),11,Ha))),128))])):b("",!0),a("div",Va,[a("nav",Ja,[a("button",{type:"button",class:z(["sec-btn",{active:h.value==="ringkasan"}]),onClick:t[0]||(t[0]=s=>rt("ringkasan"))},[...t[20]||(t[20]=[et('<span class="sec-icon" aria-hidden="true" data-v-4c3d452c><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-4c3d452c><path d="M4 19V5M4 19h16M8 16V9M12 16V7M16 16v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-4c3d452c></path></svg></span><span class="sec-text" data-v-4c3d452c><span class="sec-label" data-v-4c3d452c>Ringkasan</span><span class="sec-hint" data-v-4c3d452c>Rekap kelas &amp; tren</span></span>',2)])],2),L.value||f.value?(r(),o("button",{key:0,type:"button",class:z(["sec-btn",{active:h.value==="skor"}]),onClick:t[1]||(t[1]=s=>rt("skor"))},[...t[21]||(t[21]=[et('<span class="sec-icon" aria-hidden="true" data-v-4c3d452c><svg width="16" height="16" viewBox="0 0 24 24" fill="none" data-v-4c3d452c><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" data-v-4c3d452c></path><path d="M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" data-v-4c3d452c></path></svg></span><span class="sec-text" data-v-4c3d452c><span class="sec-label" data-v-4c3d452c>Skor siswa</span><span class="sec-hint" data-v-4c3d452c>Poin per siswa</span></span>',2)])],2)):b("",!0),a("button",{type:"button",class:z(["sec-btn",{active:h.value==="catatan"}]),onClick:t[2]||(t[2]=s=>rt("catatan"))},[t[23]||(t[23]=a("span",{class:"sec-icon","aria-hidden":"true"},[a("svg",{width:"16",height:"16",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M4 7h16M4 12h10M4 17h7",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",za,[t[22]||(t[22]=a("span",{class:"sec-label"},"Catatan",-1)),a("span",Ea,u(x.value?"Prestasi siswa":f.value?"Pelanggaran siswa":"Pelanggaran, prestasi, konseling"),1)])],2)]),a("div",Ia,[a("p",Fa,u($e.value),1),h.value==="catatan"&&(L.value||f.value||x.value)?(r(),o("div",Oa,[x.value?b("",!0):(r(),o("button",{key:0,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="violations"}]),"aria-selected":P.value==="violations",onClick:t[3]||(t[3]=s=>P.value="violations")}," Pelanggaran ("+u(((i=g.value)==null?void 0:i.total)??0)+") ",11,Ua)),f.value?b("",!0):(r(),o("button",{key:1,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="achievements"}]),"aria-selected":P.value==="achievements",onClick:t[4]||(t[4]=s=>P.value="achievements")}," Prestasi ("+u(((l=g.value)==null?void 0:l.achievements_total)??((v=(c=g.value)==null?void 0:c.achievements)==null?void 0:v.length)??0)+") ",11,Ga)),L.value?(r(),o("button",{key:2,type:"button",role:"tab",class:z(["sub-nav-btn",{active:P.value==="counseling"}]),"aria-selected":P.value==="counseling",onClick:t[5]||(t[5]=s=>P.value="counseling")}," Konseling ("+u(((d=g.value)==null?void 0:d.counseling_total)??((k=($=g.value)==null?void 0:$.counseling)==null?void 0:k.length)??0)+") ",11,qa)):b("",!0)])):b("",!0),a("div",Ya,[at(a("select",{"onUpdate:modelValue":t[6]||(t[6]=s=>p.value.academic_year_id=s),class:"filter-select",onChange:Qe},[t[24]||(t[24]=a("option",{value:""},"Semua Tahun Ajaran",-1)),(r(!0),o(S,null,B(Nt.value,s=>(r(),o("option",{key:s.id,value:String(s.id)},u(s.name),9,Za))),128))],544),[[lt,p.value.academic_year_id]]),at(a("select",{"onUpdate:modelValue":t[7]||(t[7]=s=>p.value.semester_id=s),class:"filter-select",onChange:it},[t[25]||(t[25]=a("option",{value:""},"Semua Semester",-1)),(r(!0),o(S,null,B(Kt.value,s=>(r(),o("option",{key:s.id,value:String(s.id)},u(s.name),9,Wa))),128))],544),[[lt,p.value.semester_id]]),at(a("select",{"onUpdate:modelValue":t[8]||(t[8]=s=>p.value.class_id=s),class:"filter-select",onChange:it,disabled:Z.value&&U.value.length<=1},[Z.value?b("",!0):(r(),o("option",Xa,"Semua Kelas")),(r(!0),o(S,null,B(U.value,s=>(r(),o("option",{key:s.id,value:String(s.id)},u(s.name),9,ts))),128))],40,Qa),[[lt,p.value.class_id]]),a("button",{type:"button",class:"filter-toggle","aria-expanded":E.value,onClick:t[9]||(t[9]=s=>E.value=!E.value)},[st(u(E.value?"Sembunyikan filter":"Filter lanjutan")+" ",1),a("span",as,u(E.value?"▼":"▶"),1)],8,es)]),at(a("div",ss,[at(a("select",{"onUpdate:modelValue":t[10]||(t[10]=s=>p.value.month=s),class:"filter-select",onChange:it},[t[26]||(t[26]=a("option",{value:""},"Semua Bulan",-1)),(r(),o(S,null,B(12,s=>a("option",{key:s,value:String(s)},u(W(s)),9,ns)),64))],544),[[lt,p.value.month]]),at(a("select",{"onUpdate:modelValue":t[11]||(t[11]=s=>p.value.year=s),class:"filter-select",onChange:it,title:"Tahun kalender"},[(r(),o(S,null,B(Gt,s=>a("option",{key:s,value:String(s)},"Tahun "+u(s),9,is)),64))],544),[[lt,p.value.year]])],512),[[ra,E.value]]),Xt.value.length?(r(),o("div",ls,[(r(!0),o(S,null,B(Xt.value,s=>(r(),o("button",{key:s.key,type:"button",class:"filter-chip",disabled:s.disabled,onClick:T=>je(s.key)},[st(u(s.label)+" ",1),s.disabled?b("",!0):(r(),o("span",rs,"×"))],8,os))),128))])):b("",!0),O.value?(r(),o("div",us,[oe(ya,{type:"table",rows:8,columns:7,"cell-widths":["90px","140px","160px","100px","80px","80px","1fr"]})])):h.value==="ringkasan"&&m.value?(r(),o(S,{key:3},[a("div",ds,[x.value?b("",!0):(r(),o("button",{key:0,type:"button",class:"summary-card card-warning",onClick:t[12]||(t[12]=s=>Ht("violations"))},[t[27]||(t[27]=a("span",{class:"summary-icon summary-icon-warning","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 9v4M12 17h.01",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"}),a("path",{d:"M10.3 4.3 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z",stroke:"currentColor","stroke-width":"2","stroke-linejoin":"round"})])],-1)),a("span",cs,[a("span",ps,u(((R=m.value.summary)==null?void 0:R.total_violations)??0),1),a("span",vs,"Pelanggaran · "+u(((K=m.value.summary)==null?void 0:K.total_violation_points)??0)+" poin",1)])])),f.value?b("",!0):(r(),o("button",{key:1,type:"button",class:"summary-card card-good",onClick:t[13]||(t[13]=s=>Ht("achievements"))},[t[28]||(t[28]=a("span",{class:"summary-icon summary-icon-good","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M12 2l2.4 6.9H22l-5.6 4.1 2.1 6.9L12 16.8 5.5 19.9 7.6 13 2 8.9h7.6L12 2z",stroke:"currentColor","stroke-width":"1.6","stroke-linejoin":"round"})])],-1)),a("span",hs,[a("span",ms,u(((M=m.value.summary)==null?void 0:M.total_achievements)??0),1),a("span",gs,u(A.value?"Apresiasi":"Prestasi")+" · −"+u(((V=m.value.summary)==null?void 0:V.total_achievement_points)??0)+" poin",1)])])),L.value?(r(),o("button",{key:2,type:"button",class:"summary-card card-score",onClick:t[14]||(t[14]=s=>rt("skor"))},[t[30]||(t[30]=a("span",{class:"summary-icon summary-icon-score","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("circle",{cx:"12",cy:"12",r:"9",stroke:"currentColor","stroke-width":"2"}),a("path",{d:"M12 7v5l3 2",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round"})])],-1)),a("span",_s,[a("span",ks,u(((J=m.value.summary)==null?void 0:J.net_score)??0),1),t[29]||(t[29]=a("span",{class:"summary-label"},"Skor bersih",-1))])])):b("",!0),L.value?(r(),o("button",{key:3,type:"button",class:"summary-card card-total",onClick:t[15]||(t[15]=s=>Ht("counseling"))},[t[32]||(t[32]=a("span",{class:"summary-icon summary-icon-total","aria-hidden":"true"},[a("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none"},[a("path",{d:"M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),a("span",bs,[a("span",fs,u(((C=m.value.summary)==null?void 0:C.total_counseling)??0),1),t[31]||(t[31]=a("span",{class:"summary-label"},"Sesi konseling",-1))])])):b("",!0)]),a("section",ys,[t[37]||(t[37]=a("h3",{class:"section-title"},"Per kelas",-1)),(w=m.value.by_class)!=null&&w.length?(r(),o("div",Ss,[a("table",ws,[a("thead",null,[a("tr",null,[t[35]||(t[35]=a("th",null,"Kelas",-1)),x.value?b("",!0):(r(),o("th",Cs,"Pelanggaran")),f.value?b("",!0):(r(),o("th",Ps,"Prestasi")),L.value?(r(),o("th",Ts,"Konseling")):b("",!0),t[36]||(t[36]=a("th",null,null,-1))])]),a("tbody",null,[(r(!0),o(S,null,B(m.value.by_class,s=>(r(),o("tr",{key:s.class_id??s.class_name,class:"row-clickable",onClick:T=>se(s)},[a("td",null,u(s.class_name),1),x.value?b("",!0):(r(),o("td",As,u(s.violation_count),1)),f.value?b("",!0):(r(),o("td",Bs,u(s.achievement_count??0),1)),L.value?(r(),o("td",Rs,u(s.counseling_count),1)):b("",!0),a("td",Ds,[a("button",{type:"button",class:"link-btn",onClick:ua(T=>se(s),["stop"])},u(x.value?"Lihat prestasi →":(f.value,"Skor siswa →")),9,Ns)])],8,Ls))),128))])])])):(r(),o("div",$s,[t[33]||(t[33]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),t[34]||(t[34]=a("h3",{class:"empty-title"},"Belum ada data",-1)),a("p",xs,u(A.value?"Tidak ada apresiasi untuk filter yang dipilih.":x.value?"Tidak ada prestasi untuk filter yang dipilih.":f.value?"Tidak ada pelanggaran untuk filter yang dipilih.":"Tidak ada pelanggaran, prestasi, atau konseling untuk filter yang dipilih."),1)]))]),a("section",Ks,[a("div",Ms,[a("h3",js,"Tren "+u((j=m.value.by_month)==null?void 0:j.year),1),at(a("select",{"onUpdate:modelValue":t[16]||(t[16]=s=>p.value.year=s),class:"filter-select filter-select-sm",onChange:Me,title:"Tahun kalender untuk grafik tren"},[(r(),o(S,null,B(Gt,s=>a("option",{key:"t"+s,value:String(s)},u(s),9,Hs)),64))],544),[[lt,p.value.year]])]),ae.value?(r(),o("div",Vs,[a("div",Js,[oe(da(fa),{data:ae.value,options:Ne},null,8,["data"])])])):b("",!0)]),a("div",zs,[x.value?b("",!0):(r(),o("section",Es,[t[39]||(t[39]=a("h3",{class:"section-title"},"Jenis pelanggaran",-1)),(_=m.value.top_violation_types)!=null&&_.length?(r(),o("div",Is,[a("table",Fs,[t[38]||(t[38]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(r(!0),o(S,null,B(m.value.top_violation_types,s=>(r(),o("tr",{key:s.violation_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Os,u(s.count),1),a("td",Us,u(s.total_points),1)]))),128))])])])):(r(),o("p",Gs,"Belum ada catatan pelanggaran."))])),f.value?b("",!0):(r(),o("section",qs,[t[41]||(t[41]=a("h3",{class:"section-title"},"Jenis prestasi",-1)),(ft=m.value.top_achievement_types)!=null&&ft.length?(r(),o("div",Ys,[a("table",Zs,[t[40]||(t[40]=a("thead",null,[a("tr",null,[a("th",null,"Jenis"),a("th",{class:"th-num"},"Jumlah"),a("th",{class:"th-num"},"Poin")])],-1)),a("tbody",null,[(r(!0),o(S,null,B(m.value.top_achievement_types,s=>(r(),o("tr",{key:s.achievement_type_id??s.type_name},[a("td",null,u(s.type_name),1),a("td",Ws,u(s.count),1),a("td",Qs,u(s.total_points),1)]))),128))])])])):(r(),o("p",Xs,"Belum ada catatan prestasi."))]))])],64)):h.value==="skor"?(r(),o(S,{key:4},[($t=(yt=g.value)==null?void 0:yt.by_student)!=null&&$t.length?(r(),o("div",en,[a("table",an,[t[43]||(t[43]=a("thead",null,[a("tr",null,[a("th",null,"#"),a("th",null,"NIS"),a("th",null,"Nama"),a("th",{class:"th-num"},"Pelanggaran"),a("th",{class:"th-num"},"Prestasi"),a("th",{class:"th-num"},"Skor")])],-1)),a("tbody",null,[(r(!0),o(S,null,B(Qt.value,s=>(r(),o(S,{key:"bk-"+s.key},[a("tr",sn,[a("td",nn,"Kelas "+u(s.name)+" · "+u(s.rows.length)+" siswa",1)]),(r(!0),o(S,null,B(s.rows,(T,Lt)=>(r(),o("tr",{key:T.student_id??s.key+"-"+Lt},[a("td",null,u(s.start+Lt+1),1),a("td",null,u(T.nis||"—"),1),a("td",null,u(T.student_name||"—"),1),a("td",ln,[st(u(T.violation_points??T.total_points)+" ",1),a("span",on,"("+u(T.violation_count)+")",1)]),a("td",rn,[st("−"+u(T.achievement_points??0)+" ",1),a("span",un,"("+u(T.achievement_count??0)+")",1)]),a("td",{class:z(["td-num td-total",we(T.score)])},u(T.score??(T.violation_points??T.total_points)-(T.achievement_points??0)),3)]))),128))],64))),128))])])])):(r(),o("div",tn,[...t[42]||(t[42]=[et('<div class="empty-icon" data-v-4c3d452c><svg width="80" height="80" aria-hidden="true" data-v-4c3d452c><use href="#bk-empty" data-v-4c3d452c></use></svg></div><h3 class="empty-title" data-v-4c3d452c>Belum ada data</h3><p class="empty-desc" data-v-4c3d452c>Tidak ada siswa dengan pelanggaran atau prestasi untuk filter ini.</p>',3)])]))],64)):h.value==="catatan"?(r(),o(S,{key:5},[(xt=g.value)!=null&&xt.truncated?(r(),o("p",dn,"Ditampilkan maksimal 2000 baris per jenis catatan.")):b("",!0),P.value==="violations"?(r(),o("section",cn,[(wt=(St=g.value)==null?void 0:St.items)!=null&&wt.length?(r(),o("div",vn,[a("table",hn,[t[45]||(t[45]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",{class:"th-num"},"Poin"),a("th",null,"Status")])],-1)),a("tbody",null,[(r(!0),o(S,null,B(g.value.items,s=>(r(),o("tr",{key:s.id},[a("td",null,u(Q(s.violation_date)),1),a("td",null,[a("span",mn,u(s.student_name||"—"),1),a("span",gn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.violation_type),1),a("td",_n,u(s.point_weight),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(Yt(s.status)),3)])]))),128))])])])):(r(),o("div",pn,[...t[44]||(t[44]=[et('<div class="empty-icon" data-v-4c3d452c><svg width="80" height="80" aria-hidden="true" data-v-4c3d452c><use href="#bk-empty" data-v-4c3d452c></use></svg></div><h3 class="empty-title" data-v-4c3d452c>Belum ada pelanggaran</h3><p class="empty-desc" data-v-4c3d452c>Tidak ada catatan untuk filter yang dipilih.</p>',3)])]))])):P.value==="achievements"?(r(),o("section",kn,[(ut=(Ct=g.value)==null?void 0:Ct.achievements)!=null&&ut.length?(r(),o("div",$n,[a("table",xn,[a("thead",null,[a("tr",null,[t[47]||(t[47]=a("th",null,"Tanggal",-1)),t[48]||(t[48]=a("th",null,"Siswa",-1)),t[49]||(t[49]=a("th",null,"Kelas",-1)),a("th",null,u(A.value?"Uraian":"Lomba / Kegiatan"),1),t[50]||(t[50]=a("th",null,"Jenis",-1)),Bt.value?(r(),o("th",Sn,"Tingkat")):b("",!0),t[51]||(t[51]=a("th",{class:"th-num"},"Poin",-1))])]),a("tbody",null,[(r(!0),o(S,null,B(g.value.achievements,s=>(r(),o("tr",{key:s.id},[a("td",null,u(Q(s.achievement_date)),1),a("td",null,[a("span",wn,u(s.student_name||"—"),1),a("span",Cn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.title||s.notes||"—"),1),a("td",null,u(s.achievement_type),1),Bt.value?(r(),o("td",Pn,[(s.purpose||"akreditasi")==="akreditasi"?(r(),o(S,{key:0},[st(u(Rt(s.level)),1),s.rank?(r(),o(S,{key:0},[st(" · "+u(Dt(s.rank)),1)],64)):b("",!0)],64)):(r(),o(S,{key:1},[st("—")],64))])):b("",!0),a("td",Tn,"−"+u(s.point_value),1)]))),128))])])])):(r(),o("div",bn,[t[46]||(t[46]=a("div",{class:"empty-icon"},[a("svg",{width:"80",height:"80","aria-hidden":"true"},[a("use",{href:"#bk-empty"})])],-1)),a("h3",fn,"Belum ada "+u(A.value?"apresiasi":"prestasi"),1),a("p",yn,"Tidak ada catatan "+u(A.value?"apresiasi":"prestasi")+" untuk filter yang dipilih.",1)]))])):(r(),o("section",Ln,[(Tt=(Pt=g.value)==null?void 0:Pt.counseling)!=null&&Tt.length?(r(),o("div",Bn,[a("table",Rn,[t[53]||(t[53]=a("thead",null,[a("tr",null,[a("th",null,"Tanggal"),a("th",null,"Siswa"),a("th",null,"Kelas"),a("th",null,"Jenis"),a("th",null,"Status"),a("th",null,"Konselor")])],-1)),a("tbody",null,[(r(!0),o(S,null,B(g.value.counseling,s=>(r(),o("tr",{key:s.id},[a("td",null,u(Q(s.session_date)),1),a("td",null,[a("span",Dn,u(s.student_name||"—"),1),a("span",Nn,u(s.nis||"—"),1)]),a("td",null,u(s.class_name),1),a("td",null,u(s.counseling_type),1),a("td",null,[a("span",{class:z(["status-badge","status-"+s.status])},u(Zt(s.status)),3)]),a("td",null,u(s.counselor_name||"—"),1)]))),128))])])])):(r(),o("div",An,[...t[52]||(t[52]=[et('<div class="empty-icon" data-v-4c3d452c><svg width="80" height="80" aria-hidden="true" data-v-4c3d452c><use href="#bk-empty" data-v-4c3d452c></use></svg></div><h3 class="empty-title" data-v-4c3d452c>Belum ada konseling</h3><p class="empty-desc" data-v-4c3d452c>Tidak ada sesi konseling untuk filter yang dipilih.</p>',3)])]))]))],64)):O.value?b("",!0):(r(),o("div",Kn,[...t[54]||(t[54]=[et('<div class="empty-icon" data-v-4c3d452c><svg width="80" height="80" aria-hidden="true" data-v-4c3d452c><use href="#bk-empty" data-v-4c3d452c></use></svg></div><h3 class="empty-title" data-v-4c3d452c>Gagal memuat laporan</h3><p class="empty-desc" data-v-4c3d452c>Coba ubah filter atau periksa koneksi.</p>',3)])]))])])])}}},Wn=$a(Mn,[["__scopeId","data-v-4c3d452c"]]);export{Wn as default};
