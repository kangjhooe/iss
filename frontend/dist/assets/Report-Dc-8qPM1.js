import{r as _t,l as S,o as oa,D as p,B as u,G as t,H as K,P as bt,$ as Yt,K as I,E as F,I as s,R as ia,a0 as ra,O as Xt,Y as ct,x as G,A as nt,N as da}from"./vue-Du24L0lx.js";import{L as ua}from"./LoadingSkeleton-C6AWvtAX.js";import{C as ca,a as ma,L as va,b as pa,A as ha,p as fa,e as ga,f as _a,g as yt,h as kt}from"./chart-DvVNqGk7.js";import{m as ba,_ as ya,q as wt,v as $t,a as ka}from"./index-DJ0_76y_.js";import"./pinia-DcNlwu5q.js";import"./vue-router-BFLIbfh7.js";import"./axios-C0Zqfgkc.js";const wa={getStatistics(mt=null,Z={}){const U=mt?`/v1/report/institution/${mt}`:"/v1/report/institution";return ba.get(U,{params:Z})}},$a={class:"report-page"},Ca={class:"tab-header"},xa=["disabled"],ja={class:"filters filters-inline"},Sa={class:"filter-group"},Ta=["value"],Ma={class:"filter-group"},Ra=["value"],Aa={class:"filter-group"},La={key:0,class:"loading-wrap"},Ka={key:1,class:"report-content"},Pa={class:"dashboard-mini"},Da={class:"stat-card"},Ja={class:"stat-content"},Na={class:"stat-value"},Ha={class:"stat-card"},Ga={class:"stat-content"},Va={class:"stat-value"},za={class:"stat-card"},Ba={class:"stat-content"},Ia={class:"stat-value"},Fa={class:"stat-card"},Ea={class:"stat-content"},Oa={class:"stat-value"},Ua={class:"section"},Za={class:"info-grid"},Wa={class:"info-item"},Ya={class:"info-value"},Xa={class:"info-item"},qa={class:"info-value"},Qa={class:"info-item"},te={class:"info-label"},ae={class:"info-value"},ee={class:"info-item"},se={class:"info-value"},le={class:"info-item"},ne={class:"info-value"},oe={class:"info-item"},ie={class:"info-value"},re={class:"info-item"},de={class:"info-label"},ue={class:"info-value"},ce={class:"info-item"},me={class:"info-label"},ve={class:"info-value"},pe={class:"section"},he={key:0,class:"chart-container"},fe={class:"chart-wrapper"},ge={key:1,class:"chart-placeholder"},_e={class:"chart-wrapper"},be={key:1,class:"chart-placeholder"},ye={key:1,class:"data-table-container"},ke={key:0,class:"table-period-hint"},we={class:"data-table data-table-student-report"},$e={key:0,class:"total-row"},Ce={key:2,class:"data-table-container"},xe={class:"data-table"},je={class:"total-row"},Se={class:"section"},Te={class:"chart-container"},Me={class:"chart-wrapper"},Re={class:"data-table-container"},Ae={class:"data-table"},Le={key:0,class:"section"},Ke={class:"info-grid",style:{"margin-bottom":"20px"}},Pe={class:"info-item"},De={class:"info-value"},Je={class:"info-item"},Ne={class:"info-value"},He={class:"info-item"},Ge={class:"info-value"},Ve={style:{"margin-bottom":"12px",color:"#475569","font-size":"16px"}},ze={class:"data-table-container"},Be={class:"data-table"},Ie={class:"section"},Fe={class:"chart-container"},Ee={class:"chart-wrapper"},Oe={class:"chart-wrapper"},Ue={class:"data-table-container"},Ze={class:"data-table"},We={class:"total-row"},Ye={class:"section"},Xe={class:"facilities-grid"},qe={class:"facility-card"},Qe={class:"facility-stats"},ts={class:"facility-stat"},as={class:"facility-value"},es={class:"facility-stat"},ss={class:"facility-value"},ls={class:"facility-card"},ns={class:"facility-stats"},os={class:"facility-stat"},is={class:"facility-value"},rs={class:"facility-stat"},ds={class:"facility-value"},us={class:"facility-card"},cs={class:"facility-stats"},ms={class:"facility-stat"},vs={class:"facility-value"},ps={class:"rooms-by-type"},hs={class:"rooms-table"},fs={class:"section"},gs={class:"ratios-grid"},_s={class:"ratio-card"},bs={class:"ratio-content"},ys={class:"ratio-value"},ks={class:"ratio-card"},ws={class:"ratio-content"},$s={class:"ratio-value"},Cs={key:0,class:"ratio-card"},xs={class:"ratio-content"},js={class:"ratio-value"},Ss={key:1,class:"section"},Ts={class:"comparison-container"},Ms={class:"comparison-chart"},Rs={__name:"Report",setup(mt){ca.register(ma,va,pa,ha,fa,ga,_a);const Z=ka(),U=_t(!1),e=_t(null),$=_t({month:(new Date().getMonth()+1).toString(),year:new Date().getFullYear().toString(),compareWithPrevious:!1}),Ct=[{value:"1",label:"Januari"},{value:"2",label:"Februari"},{value:"3",label:"Maret"},{value:"4",label:"April"},{value:"5",label:"Mei"},{value:"6",label:"Juni"},{value:"7",label:"Juli"},{value:"8",label:"Agustus"},{value:"9",label:"September"},{value:"10",label:"Oktober"},{value:"11",label:"November"},{value:"12",label:"Desember"}],qt=S(()=>{const l=new Date().getFullYear(),a=[];for(let i=l;i>=l-5;i--)a.push(i.toString());return a}),xt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"top"},title:{display:!1}}},vt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom"}}},Qt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"top"}},scales:{y:{beginAtZero:!0}}},V=S(()=>{var i,v;if(!((v=(i=e.value)==null?void 0:i.institution)!=null&&v.level))return[7,8,9];const l=e.value.institution.level;return{TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[l]||[7,8,9]}),jt=l=>{const a=Number(l);return a===10?"X":a===11?"XI":a===12?"XII":String(l)},b=S(()=>{var N,M,C,f,P;const l=(N=e.value)==null?void 0:N.students_table_detail;if(l!=null&&l.by_grade)return l;const a=(M=e.value)==null?void 0:M.students,i=(C=e.value)==null?void 0:C.classes_detail;if(!a||!((P=(f=e.value)==null?void 0:f.institution)!=null&&P.level))return null;const v=e.value.institution.level,o={TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[v]||[7,8,9],c={};let r=0;const y={jml_romb:0,jumlah_awal:{male:0,female:0,total:0},siswa_keluar:{male:0,female:0,total:0},siswa_masuk:{male:0,female:0,total:0},jumlah_akhir:{male:0,female:0,total:0}};for(const L of o){const D=`grade_${L}`,x=a[D]||{male:0,female:0,total:0},z=i!=null&&i.by_grade&&Array.isArray(i.by_grade[D])?i.by_grade[D].length:0;c[D]={grade:L,jml_romb:z,jumlah_awal:{...x},siswa_keluar:{male:0,female:0,total:0},siswa_masuk:{male:0,female:0,total:0},jumlah_akhir:{...x}},r+=z,y.jumlah_awal.male+=x.male,y.jumlah_awal.female+=x.female,y.jumlah_awal.total+=x.total,y.siswa_keluar.total+=0,y.siswa_masuk.total+=0,y.jumlah_akhir.male+=x.male,y.jumlah_akhir.female+=x.female,y.jumlah_akhir.total+=x.total}return y.jml_romb=r,{grade_range:[o[0],o[o.length-1]],by_grade:c,totals:y}}),ta=S(()=>{const l=b.value;if(!(l!=null&&l.by_grade))return[];const[a,i]=l.grade_range||[1,12],v=[];for(let d=a;d<=i;d++){const o=`grade_${d}`,c=l.by_grade[o];c&&v.push({grade:d,gradeLabel:jt(d),jml_romb:c.jml_romb??0,jumlah_awal:c.jumlah_awal??{male:0,female:0,total:0},siswa_keluar:c.siswa_keluar??{male:0,female:0,total:0},siswa_masuk:c.siswa_masuk??{male:0,female:0,total:0},jumlah_akhir:c.jumlah_akhir??{male:0,female:0,total:0}})}return v}),St=l=>["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"][Number(l)]||l,Tt=S(()=>{if(!e.value||!e.value.students)return null;const l=V.value,a=l.map(d=>`Kelas ${d}`),i=l.map(d=>{var o;return((o=e.value.students[`grade_${d}`])==null?void 0:o.male)||0}),v=l.map(d=>{var o;return((o=e.value.students[`grade_${d}`])==null?void 0:o.female)||0});return{labels:a,datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:i},{label:"Perempuan",backgroundColor:"#f093fb",data:v}]}}),Mt=S(()=>{if(!e.value||!e.value.students)return null;const l=V.value;return{labels:l.map(v=>`Kelas ${v}`),datasets:[{backgroundColor:["#059669","#f093fb","#4facfe","#43e97b","#38f9d7","#f5576c","#047857","#059669","#f093fb","#4facfe","#43e97b","#38f9d7"].slice(0,l.length),data:l.map(v=>{var d;return((d=e.value.students[`grade_${v}`])==null?void 0:d.total)||0})}]}});S(()=>{var i,v,d,o;if(!e.value)return null;const l=((i=e.value.employees)==null?void 0:i.teachers_male)||((v=e.value.teachers)==null?void 0:v.male)||0,a=((d=e.value.employees)==null?void 0:d.teachers_female)||((o=e.value.teachers)==null?void 0:o.female)||0;return{labels:["Guru"],datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:[l]},{label:"Perempuan",backgroundColor:"#f093fb",data:[a]}]}});const Rt=S(()=>{if(!e.value||!e.value.employees)return null;const l=e.value.employees.teachers||0,a=e.value.employees.staff||0;return{labels:["Guru","Tenaga Administrasi/Staff"],datasets:[{backgroundColor:["#059669","#f093fb"],data:[l,a]}]}}),At=S(()=>{if(!e.value||!e.value.employees)return null;const l=e.value.employees.male||0,a=e.value.employees.female||0;return{labels:["Tenaga Kependidikan"],datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:[l]},{label:"Perempuan",backgroundColor:"#f093fb",data:[a]}]}}),Lt=S(()=>{if(!e.value||!e.value.students_by_status)return null;const l=e.value.students_by_status,a=[],i=[],v=["#10b981","#059669","#f59e0b","#ef4444","#94a3b8"];for(const[d,o]of Object.entries(l))d!=="total"&&o>0&&(a.push(d),i.push(o));return{labels:a,datasets:[{backgroundColor:v.slice(0,a.length),data:i}]}}),aa=S(()=>{var d;if(!e.value||!e.value.comparison||!e.value.students)return null;const l=e.value.students,a=e.value.comparison.students,i=V.value;return{labels:i.map(o=>`Kelas ${o}`),datasets:[{label:((d=e.value.academic_year)==null?void 0:d.name)||"Tahun Ajaran Aktif",backgroundColor:"#059669",data:i.map(o=>{var c;return((c=l[`grade_${o}`])==null?void 0:c.total)||0})},{label:e.value.comparison.academic_year||"Tahun Ajaran Sebelumnya",backgroundColor:"#94a3b8",data:i.map(o=>{var c;return((c=a[`grade_${o}`])==null?void 0:c.total)||0})}]}}),pt=S(()=>!e.value||!e.value.students?0:V.value.reduce((l,a)=>{var i;return l+(((i=e.value.students[`grade_${a}`])==null?void 0:i.male)||0)},0)),ht=S(()=>!e.value||!e.value.students?0:V.value.reduce((l,a)=>{var i;return l+(((i=e.value.students[`grade_${a}`])==null?void 0:i.female)||0)},0)),ot=S(()=>!e.value||!e.value.students?0:pt.value+ht.value),it=async()=>{var l,a,i,v,d;U.value=!0;try{const o={};$.value.month&&(o.month=$.value.month),$.value.year&&(o.year=$.value.year),$.value.compareWithPrevious&&(o.compare_with_previous=!0);const c=await wa.getStatistics(null,o);if(c.data&&c.data.data){const r=c.data.data,y=(l=r.institution)==null?void 0:l.level,M={TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[y]||[7,8,9];if(r.students?M.forEach(C=>{const f=`grade_${C}`;r.students[f]?(r.students[f].male=Number(r.students[f].male)||0,r.students[f].female=Number(r.students[f].female)||0,r.students[f].total=r.students[f].male+r.students[f].female):r.students[f]={male:0,female:0,total:0}}):(r.students={},M.forEach(C=>{r.students[`grade_${C}`]={male:0,female:0,total:0}})),r.summary){const C=M.reduce((f,P)=>{var L;return f+(((L=r.students[`grade_${P}`])==null?void 0:L.total)||0)},0);r.summary.total_students=C}r.employees&&!r.teachers?r.teachers={male:r.employees.teachers_male||0,female:r.employees.teachers_female||0,total:r.employees.teachers||0}:r.teachers||(r.teachers={male:0,female:0,total:0}),r.students_by_status||(r.students_by_status={Aktif:0,Lulus:0,Pindah:0,"Drop Out":0,Lainnya:0,total:0}),r.classes_detail||(r.classes_detail={by_grade:{},total_rombel:0,total_capacity:0,total_students:0,average_capacity:0,average_students_per_rombel:0}),e.value=r}else throw new Error("Format data tidak valid")}catch(o){let c="Gagal memuat data laporan";(i=(a=o.response)==null?void 0:a.data)!=null&&i.message?c=o.response.data.message:(d=(v=o.response)==null?void 0:v.data)!=null&&d.error?c=o.response.data.error:o.formattedMessage?c=o.formattedMessage:o.message&&(c=o.message),Z.error("Gagal",c)}finally{U.value=!1}},T=l=>String(l??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;"),Kt=l=>[l.address,l.village?`Desa/Kel. ${l.village}`:"",l.sub_district?`Kec. ${l.sub_district}`:"",l.district,l.province,l.postal_code].filter(Boolean).join(", ")||"-",rt=l=>l?new Intl.NumberFormat("id-ID").format(l):"0",ea=l=>$t(l==null?void 0:l.level),sa=async()=>{var l,a,i,v,d,o,c,r,y,N,M,C,f,P,L,D,x,z,W,Y,X,q,Q,tt,at,n,j,k,E,et,st,lt,Pt,Dt,Jt,Nt,Ht;if(!e.value){Z.error("Gagal","Tidak ada data untuk diekspor");return}try{const m=e.value.institution,dt=window.open("","_blank"),la=`Laporan_${m.name}_${new Date().toISOString().split("T")[0]}.pdf`,Gt=Kt(m),Vt=ea(m),H=b.value||e.value.students_table_detail;let ft;if(H!=null&&H.by_grade&&(H!=null&&H.totals)){const[w,R]=H.grade_range||[1,12],g=h=>{var A,J,ut,zt,Bt,It,Ft,Et,Ot,Ut,Zt,Wt;return[((A=h.jumlah_awal)==null?void 0:A.male)??0,((J=h.jumlah_awal)==null?void 0:J.female)??0,((ut=h.jumlah_awal)==null?void 0:ut.total)??0,((zt=h.siswa_keluar)==null?void 0:zt.male)??"",((Bt=h.siswa_keluar)==null?void 0:Bt.female)??"",((It=h.siswa_keluar)==null?void 0:It.total)??"",((Ft=h.siswa_masuk)==null?void 0:Ft.male)??"",((Et=h.siswa_masuk)==null?void 0:Et.female)??"",((Ot=h.siswa_masuk)==null?void 0:Ot.total)??"",((Ut=h.jumlah_akhir)==null?void 0:Ut.male)??0,((Zt=h.jumlah_akhir)==null?void 0:Zt.female)??0,((Wt=h.jumlah_akhir)==null?void 0:Wt.total)??0]},B=[];for(let h=w;h<=R;h++){const A=H.by_grade[`grade_${h}`];if(!A)continue;const J=g(A);B.push(`<tr><td><strong>${jt(h)}</strong></td><td>${A.jml_romb??0}</td>${J.map(ut=>`<td>${ut}</td>`).join("")}</tr>`)}const _=H.totals,O=[((l=_.jumlah_awal)==null?void 0:l.male)??0,((a=_.jumlah_awal)==null?void 0:a.female)??0,((i=_.jumlah_awal)==null?void 0:i.total)??0,((v=_.siswa_keluar)==null?void 0:v.male)??0,((d=_.siswa_keluar)==null?void 0:d.female)??0,((o=_.siswa_keluar)==null?void 0:o.total)??0,((c=_.siswa_masuk)==null?void 0:c.male)??0,((r=_.siswa_masuk)==null?void 0:r.female)??0,((y=_.siswa_masuk)==null?void 0:y.total)??0,((N=_.jumlah_akhir)==null?void 0:N.male)??0,((M=_.jumlah_akhir)==null?void 0:M.female)??0,((C=_.jumlah_akhir)==null?void 0:C.total)??0].map(h=>`<td><strong>${h}</strong></td>`).join("");ft=`
        <p class="table-period-hint">Periode: ${St(((f=e.value.period)==null?void 0:f.month)??$.value.month)} ${((P=e.value.period)==null?void 0:P.year)||$.value.year||""}</p>
        <table class="data-table data-table-student-report">
          <thead>
            <tr>
              <th rowspan="2" class="col-kls">Kls</th>
              <th rowspan="2" class="col-romb">Jumlah Rombel</th>
              <th colspan="3">Jumlah Awal</th>
              <th colspan="3">Siswa Keluar</th>
              <th colspan="3">Siswa Masuk</th>
              <th colspan="3">Jumlah Akhir</th>
            </tr>
            <tr>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
              <th>L</th><th>P</th><th>Jml</th>
            </tr>
          </thead>
          <tbody>
            ${B.join("")}
            <tr class="total-row"><td><strong>Jml</strong></td><td><strong>${_.jml_romb??0}</strong></td>${O}</tr>
          </tbody>
        </table>
      `}else ft=`
        <table class="data-table">
          <thead>
            <tr>
              <th>Kelas</th>
              <th>Laki-laki</th>
              <th>Perempuan</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${V.value.map(g=>{var B,_,O,h,A,J;return`
      <tr>
        <td><strong>Kelas ${g}</strong></td>
        <td>${((_=(B=e.value.students)==null?void 0:B[`grade_${g}`])==null?void 0:_.male)||0}</td>
        <td>${((h=(O=e.value.students)==null?void 0:O[`grade_${g}`])==null?void 0:h.female)||0}</td>
        <td><strong>${((J=(A=e.value.students)==null?void 0:A[`grade_${g}`])==null?void 0:J.total)||0}</strong></td>
      </tr>
    `}).join("")+`
      <tr>
        <td><strong>Total</strong></td>
        <td><strong>${pt.value}</strong></td>
        <td><strong>${ht.value}</strong></td>
        <td><strong>${ot.value||((L=e.value.summary)==null?void 0:L.total_students)||0}</strong></td>
      </tr>
    `}
          </tbody>
        </table>
      `;let gt="";if(e.value.facilities.rooms.by_type)for(const[w,R]of Object.entries(e.value.facilities.rooms.by_type))gt+=`<tr><td>${w}</td><td><strong>${R}</strong></td></tr>`;const na=`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>${T(`Laporan ${m.name||""}`)}</title>
        <style>
        @media print {
          @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 10mm;
          }
          body { margin: 0; }
        }
        body {
          font-family: Arial, Helvetica, sans-serif;
          font-size: 12px;
          line-height: 1.3;
          color: #111;
          margin: 16px;
          padding: 0;
        }
        .kop { border-bottom: 3px double #111; padding: 0 8px 8px; margin-bottom: 10px; }
        .kop-inner { display: grid; grid-template-columns: 76px 1fr 76px; align-items: center; min-height: 70px; }
        .kop-logo { width: 66px; height: 66px; object-fit: contain; }
        .kop-text { min-width: 0; text-align: center; }
        .foundation { overflow: hidden; font-family: "Times New Roman", serif; font-size: 14px; font-weight: 600; line-height: 1.15; text-transform: uppercase; text-overflow: ellipsis; white-space: nowrap; letter-spacing: 0.02em; }
        .school { font-family: "Times New Roman", serif; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .school-address { font-size: 10px; line-height: 1.35; margin-top: 3px; }
        .school-info { font-size: 9px; margin-top: 2px; }
        .header {
          text-align: center;
          margin-bottom: 20px;
          margin-top: 15px;
        }
        .header h1 {
          color: #000;
          margin: 0;
          font-size: 18px;
          font-weight: bold;
          text-transform: uppercase;
        }
        .section {
          margin-bottom: 20px;
          page-break-inside: avoid;
        }
        .section-title {
          background: #f0f0f0;
          color: #000;
          padding: 8px 12px;
          margin: 0 0 12px 0;
          font-size: 14px;
          font-weight: bold;
          border-left: 4px solid #000;
        }
        .info-grid {
          display: grid;
          grid-template-columns: 1fr 2fr;
          gap: 8px 16px;
          margin-bottom: 12px;
        }
        .info-item {
          display: contents;
        }
        .info-label {
          font-weight: bold;
          font-size: 12px;
        }
        .info-value {
          font-size: 12px;
        }
        .data-table {
          width: calc(100% - 2px);
          max-width: calc(100% - 2px);
          border-collapse: collapse;
          margin-top: 12px;
          font-size: 12px;
        }
        .data-table th,
        .data-table td {
          border: 1px solid #000;
          padding: 8px;
          text-align: left;
        }
        .data-table th {
          background: #f0f0f0;
          font-weight: bold;
          text-align: center;
        }
        .data-table td {
          text-align: center;
        }
        .total-row {
          background: #f0f0f0;
          font-weight: bold;
        }
        .table-period-hint {
          margin-bottom: 8px;
          font-size: 12px;
          color: #333;
        }
        .data-table-student-report th,
        .data-table-student-report td {
          text-align: center;
        }
        .summary-box {
          background: #f9f9f9;
          border: 1px solid #ddd;
          padding: 12px;
          margin-bottom: 20px;
        }
        .summary-grid {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 12px;
        }
        .summary-item {
          text-align: center;
        }
        .summary-value {
          font-size: 24px;
          font-weight: bold;
          color: #059669;
        }
        .summary-label {
          font-size: 12px;
          color: #666;
        }
        .footer {
          margin-top: 40px;
          display: flex;
          justify-content: space-between;
          page-break-inside: avoid;
        }
        .footer-date {
          font-size: 11px;
        }
        .footer-signature {
          text-align: center;
        }
        .footer-signature-label {
          margin-bottom: 60px;
          font-weight: bold;
        }
        .footer-signature-name {
          font-weight: bold;
          text-decoration: underline;
        }
        .footer-signature-nip {
          font-size: 11px;
          margin-top: 5px;
        }
        </style>
      </head>
      <body>
        <header class="kop">
          <div class="kop-inner">
            <div>${m.logo?`<img src="${T(m.logo)}" alt="Logo institusi" class="kop-logo" />`:""}</div>
            <div class="kop-text">
              ${m.foundation_name?`<div class="foundation">${T(m.foundation_name)}</div>`:""}
              <div class="school">${T(m.name||"NAMA LEMBAGA")}</div>
              <div class="school-address">${T(Gt||"-")}</div>
              <div class="school-info">
                NPSN: ${T(m.npsn||"-")}
                ${m.nss?` · ${wt(m.level)}: ${T(m.nss)}`:""}
                ${m.phone?` · Telp: ${T(m.phone)}`:""}
                ${m.email?` · Email: ${T(m.email)}`:""}
                ${m.website?` · ${T(m.website)}`:""}
              </div>
            </div>
            <div></div>
          </div>
        </header>
        
        <div class="header">
          <h1>LAPORAN STATISTIK LEMBAGA</h1>
          <p>Tahun Ajaran: ${((D=e.value.academic_year)==null?void 0:D.name)||"-"}</p>
          <p>Periode: ${$.value.month?(x=Ct.find(w=>w.value===$.value.month))==null?void 0:x.label:"Semua"} ${$.value.year||""}</p>
        </div>
        
        <div class="section">
          <h3 class="section-title">Ringkasan Eksekutif</h3>
          <div class="summary-box">
            <div class="summary-grid">
              <div class="summary-item">
                <div class="summary-value">${ot.value||((z=e.value.summary)==null?void 0:z.total_students)||0}</div>
                <div class="summary-label">Total Siswa</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${((W=e.value.summary)==null?void 0:W.total_teachers)||0}</div>
                <div class="summary-label">Total Guru</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${((Y=e.value.summary)==null?void 0:Y.total_staff)||0}</div>
                <div class="summary-label">Total Staff</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${((X=e.value.summary)==null?void 0:X.total_classes)||0}</div>
                <div class="summary-label">Total Kelas</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${((q=e.value.classes_detail)==null?void 0:q.total_rombel)||0}</div>
                <div class="summary-label">Total Rombel</div>
              </div>
              <div class="summary-item">
                <div class="summary-value">${((Q=e.value.summary)==null?void 0:Q.total_facilities)||0}</div>
                <div class="summary-label">Sarana Prasarana</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="section">
          <h3 class="section-title">Identitas Lembaga</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Nama Lembaga</span>
              <span class="info-value">${m.name}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NPSN</span>
              <span class="info-value">${m.npsn||"-"}</span>
            </div>
            <div class="info-item">
              <span class="info-label">${wt(m.level)}</span>
              <span class="info-value">${m.nss||"-"}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Level</span>
              <span class="info-value">${m.level||"-"}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Tipe</span>
              <span class="info-value">${m.type||"-"}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Alamat</span>
              <span class="info-value">${Gt}</span>
            </div>
            <div class="info-item">
              <span class="info-label">${Vt}</span>
              <span class="info-value">${m.principal_name||"-"}</span>
            </div>
            <div class="info-item">
              <span class="info-label">NIP</span>
              <span class="info-value">${m.principal_nip||"-"}</span>
            </div>
          </div>
        </div>
        
        <div class="section">
          <h3 class="section-title">Data Siswa</h3>
          ${ft}
        </div>
        
        ${e.value.students_by_status?`
        <div class="section">
          <h3 class="section-title">Status Siswa</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Status</th>
                <th>Jumlah</th>
                <th>Persentase</th>
              </tr>
            </thead>
            <tbody>
              ${Object.entries(e.value.students_by_status).filter(([w])=>w!=="total").map(([w,R])=>{var g;return`
              <tr>
                <td><strong>${w}</strong></td>
                <td>${R}</td>
                <td>${((g=e.value.students_by_status)==null?void 0:g.total)>0?(R/e.value.students_by_status.total*100).toFixed(2):0}%</td>
              </tr>
              `}).join("")}
            </tbody>
          </table>
        </div>
        `:""}
        
        ${e.value.classes_detail&&e.value.classes_detail.total_rombel>0?`
        <div class="section">
          <h3 class="section-title">Rombongan Belajar (Rombel)</h3>
          <div class="info-grid" style="margin-bottom: 12px;">
            <div class="info-item">
              <span class="info-label">Total Rombel</span>
              <span class="info-value">${e.value.classes_detail.total_rombel||0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Rombel</span>
              <span class="info-value">${e.value.classes_detail.average_students_per_rombel||0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Kapasitas per Rombel</span>
              <span class="info-value">${e.value.classes_detail.average_capacity||0}</span>
            </div>
          </div>
          ${Object.entries(e.value.classes_detail.by_grade||{}).map(([w,R])=>`
          <h4 style="margin-top: 16px; margin-bottom: 8px; font-size: 13px; font-weight: bold;">${w.replace("grade_","Kelas ")}</h4>
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama Rombel</th>
                <th>Kode</th>
                <th>Wali Kelas</th>
                <th>Ruangan</th>
                <th>Siswa</th>
                <th>Kapasitas</th>
                <th>Utilisasi</th>
              </tr>
            </thead>
            <tbody>
              ${R.map(g=>`
              <tr>
                <td>${g.name}</td>
                <td>${g.code||"-"}</td>
                <td>${g.wali_kelas}</td>
                <td>${g.room}</td>
                <td>${g.students}</td>
                <td>${g.capacity||"-"}</td>
                <td>${g.utilization}%</td>
              </tr>
              `).join("")}
            </tbody>
          </table>
          `).join("")}
        </div>
        `:""}
        
        <div class="section">
          <h3 class="section-title">Data Tenaga Kependidikan</h3>
          <table class="data-table">
            <thead>
              <tr>
                <th>Kategori</th>
                <th>Laki-laki</th>
                <th>Perempuan</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Guru</strong></td>
                <td>${((tt=e.value.employees)==null?void 0:tt.teachers_male)||0}</td>
                <td>${((at=e.value.employees)==null?void 0:at.teachers_female)||0}</td>
                <td><strong>${((n=e.value.employees)==null?void 0:n.teachers)||0}</strong></td>
              </tr>
              <tr>
                <td><strong>Tenaga Administrasi/Staff</strong></td>
                <td>${((j=e.value.employees)==null?void 0:j.staff_male)||0}</td>
                <td>${((k=e.value.employees)==null?void 0:k.staff_female)||0}</td>
                <td><strong>${((E=e.value.employees)==null?void 0:E.staff)||0}</strong></td>
              </tr>
              <tr class="total-row">
                <td><strong>Total Tenaga Kependidikan</strong></td>
                <td><strong>${((et=e.value.employees)==null?void 0:et.male)||0}</strong></td>
                <td><strong>${((st=e.value.employees)==null?void 0:st.female)||0}</strong></td>
                <td><strong>${((lt=e.value.employees)==null?void 0:lt.total)||0}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="section">
          <h3 class="section-title">Sarana Prasarana</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Jumlah Tanah</span>
              <span class="info-value">${e.value.facilities.land.count}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Luas Tanah (m²)</span>
              <span class="info-value">${rt(e.value.facilities.land.total_area)}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Jumlah Gedung</span>
              <span class="info-value">${e.value.facilities.buildings.count}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Luas Gedung (m²)</span>
              <span class="info-value">${rt(e.value.facilities.buildings.total_area)}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Total Ruangan</span>
              <span class="info-value">${e.value.facilities.rooms.count}</span>
            </div>
          </div>
          ${gt?`
          <h4 style="margin-top: 16px; font-size: 13px; font-weight: bold;">Ruangan per Jenis</h4>
          <table class="data-table">
            <thead>
              <tr>
                <th>Jenis Ruangan</th>
                <th>Jumlah</th>
              </tr>
            </thead>
            <tbody>
              ${gt}
            </tbody>
          </table>
          `:""}
        </div>
        
        <div class="section">
          <h3 class="section-title">Rasio dan Indikator</h3>
          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Rasio Siswa : Guru</span>
              <span class="info-value">${((Pt=e.value.summary)==null?void 0:Pt.student_teacher_ratio)||0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Kelas</span>
              <span class="info-value">${((Dt=e.value.summary)==null?void 0:Dt.average_students_per_class)||0}</span>
            </div>
            ${(Jt=e.value.summary)!=null&&Jt.average_students_per_rombel?`
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Rombel</span>
              <span class="info-value">${e.value.summary.average_students_per_rombel}</span>
            </div>
            `:""}
          </div>
        </div>
        
        ${e.value.comparison?`
        <div class="section">
          <h3 class="section-title">Perbandingan dengan Tahun Ajaran Sebelumnya</h3>
          <p><strong>Tahun Ajaran Aktif:</strong> ${((Nt=e.value.academic_year)==null?void 0:Nt.name)||"-"}</p>
          <p><strong>Tahun Ajaran Sebelumnya:</strong> ${e.value.comparison.academic_year}</p>
          <table class="data-table">
            <thead>
              <tr>
                <th>Kelas</th>
                <th>${((Ht=e.value.academic_year)==null?void 0:Ht.name)||"Tahun Aktif"}</th>
                <th>${e.value.comparison.academic_year}</th>
                <th>Selisih</th>
              </tr>
            </thead>
            <tbody>
              ${V.value.map(w=>{var R,g,B,_,O,h,A,J;return`
              <tr>
                <td>Kelas ${w}</td>
                <td>${((g=(R=e.value.students)==null?void 0:R[`grade_${w}`])==null?void 0:g.total)||0}</td>
                <td>${((_=(B=e.value.comparison.students)==null?void 0:B[`grade_${w}`])==null?void 0:_.total)||0}</td>
                <td>${(((h=(O=e.value.students)==null?void 0:O[`grade_${w}`])==null?void 0:h.total)||0)-(((J=(A=e.value.comparison.students)==null?void 0:A[`grade_${w}`])==null?void 0:J.total)||0)}</td>
              </tr>
              `}).join("")}
            </tbody>
          </table>
        </div>
        `:""}
        
        <div class="footer">
          <div class="footer-left">
            <div class="footer-date">
              <strong>Dibuat pada:</strong><br>
              ${new Date().toLocaleDateString("id-ID",{day:"numeric",month:"long",year:"numeric",hour:"2-digit",minute:"2-digit"})}
            </div>
          </div>
          <div class="footer-right">
            <div class="footer-date">
              ${m.district||"Kota/Kabupaten"}, ${new Date().toLocaleDateString("id-ID",{day:"numeric",month:"long",year:"numeric"})}
            </div>
            <div class="footer-signature">
              <div class="footer-signature-label">${T(Vt)}</div>
              <div class="footer-signature-name">${T(m.principal_name||"___________________")}</div>
              <div class="footer-signature-nip">NIP. ${T(m.principal_nip||"___________________")}</div>
            </div>
          </div>
        </div>
      </body>
      </html>
    `;dt.document.write(na),dt.document.close(),setTimeout(()=>{dt.print(),dt.document.title=la},250)}catch{Z.error("Gagal","Gagal mengekspor PDF")}};return oa(()=>{it()}),(l,a)=>{var i,v,d,o,c,r,y,N,M,C,f,P,L,D,x,z,W,Y,X,q,Q,tt,at;return u(),p("div",$a,[t("div",Ca,[a[4]||(a[4]=t("div",{class:"filters filters-inline"},null,-1)),t("button",{onClick:sa,class:"btn-primary btn-compact",disabled:U.value},[...a[3]||(a[3]=[t("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M6 9V2H18V9",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("path",{d:"M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("path",{d:"M18 14H6V22H18V14Z",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})],-1),t("span",null,"Export PDF",-1)])],8,xa)]),t("div",ja,[t("div",Sa,[a[6]||(a[6]=t("label",null,"Bulan",-1)),bt(t("select",{"onUpdate:modelValue":a[0]||(a[0]=n=>$.value.month=n),onChange:it,class:"filter-select"},[a[5]||(a[5]=t("option",{value:""},"Semua Bulan",-1)),(u(),p(I,null,F(Ct,n=>t("option",{key:n.value,value:n.value},s(n.label),9,Ta)),64))],544),[[Yt,$.value.month]])]),t("div",Ma,[a[8]||(a[8]=t("label",null,"Tahun",-1)),bt(t("select",{"onUpdate:modelValue":a[1]||(a[1]=n=>$.value.year=n),onChange:it,class:"filter-select"},[a[7]||(a[7]=t("option",{value:""},"Semua Tahun",-1)),(u(!0),p(I,null,F(qt.value,n=>(u(),p("option",{key:n,value:n},s(n),9,Ra))),128))],544),[[Yt,$.value.year]])]),t("div",Aa,[t("label",null,[bt(t("input",{type:"checkbox","onUpdate:modelValue":a[2]||(a[2]=n=>$.value.compareWithPrevious=n),onChange:it},null,544),[[ra,$.value.compareWithPrevious]]),a[9]||(a[9]=ia(" Bandingkan dengan Tahun Ajaran Sebelumnya ",-1))])])]),U.value?(u(),p("div",La,[Xt(ua,{type:"card",lines:6,"line-widths":["100%","80%","60%","100%","70%","50%"]})])):e.value?(u(),p("div",Ka,[t("div",Pa,[t("div",Da,[a[11]||(a[11]=t("div",{class:"stat-icon",style:{background:"linear-gradient(135deg, #059669 0%, #047857 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("circle",{cx:"12",cy:"7",r:"4",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",Ja,[t("div",Na,s(((i=e.value.summary)==null?void 0:i.total_students)??ot.value),1),a[10]||(a[10]=t("div",{class:"stat-label"},"Total Siswa",-1))])]),t("div",Ha,[a[13]||(a[13]=ct('<div class="stat-icon" style="background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" data-v-6cc9cf88><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-6cc9cf88><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></circle><path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path></svg></div>',1)),t("div",Ga,[t("div",Va,s(((v=e.value.summary)==null?void 0:v.total_teachers)||0),1),a[12]||(a[12]=t("div",{class:"stat-label"},"Total Guru",-1))])]),t("div",za,[a[15]||(a[15]=ct('<div class="stat-icon" style="background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" data-v-6cc9cf88><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-6cc9cf88><path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path></svg></div>',1)),t("div",Ba,[t("div",Ia,s(((d=e.value.summary)==null?void 0:d.total_classes)||0),1),a[14]||(a[14]=t("div",{class:"stat-label"},"Total Kelas",-1))])]),t("div",Fa,[a[17]||(a[17]=t("div",{class:"stat-icon",style:{background:"linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V10M19 10L21 12M19 10L12 3L5 10",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",Ea,[t("div",Oa,s(((o=e.value.summary)==null?void 0:o.total_facilities)||0),1),a[16]||(a[16]=t("div",{class:"stat-label"},"Sarana Prasarana",-1))])])]),t("div",Ua,[a[23]||(a[23]=t("h3",{class:"section-title"},"Identitas Lembaga",-1)),t("div",Za,[t("div",Wa,[a[18]||(a[18]=t("span",{class:"info-label"},"Nama Lembaga",-1)),t("span",Ya,s(e.value.institution.name),1)]),t("div",Xa,[a[19]||(a[19]=t("span",{class:"info-label"},"NPSN",-1)),t("span",qa,s(e.value.institution.npsn||"-"),1)]),t("div",Qa,[t("span",te,s(G(wt)((c=e.value.institution)==null?void 0:c.level)),1),t("span",ae,s(e.value.institution.nss||"-"),1)]),t("div",ee,[a[20]||(a[20]=t("span",{class:"info-label"},"Level",-1)),t("span",se,s(e.value.institution.level||"-"),1)]),t("div",le,[a[21]||(a[21]=t("span",{class:"info-label"},"Tipe",-1)),t("span",ne,s(e.value.institution.type||"-"),1)]),t("div",oe,[a[22]||(a[22]=t("span",{class:"info-label"},"Alamat",-1)),t("span",ie,s(Kt(e.value.institution)),1)]),t("div",re,[t("span",de,s(G($t)((r=e.value.institution)==null?void 0:r.level)),1),t("span",ue,s(e.value.institution.principal_name||"-"),1)]),t("div",ce,[t("span",me,"NIP "+s(G($t)((y=e.value.institution)==null?void 0:y.level)),1),t("span",ve,s(e.value.institution.principal_nip||"-"),1)])])]),t("div",pe,[a[30]||(a[30]=t("h3",{class:"section-title"},"Data Siswa",-1)),b.value?K("",!0):(u(),p("div",he,[t("div",fe,[a[24]||(a[24]=t("h4",null,"Jumlah Siswa per Kelas",-1)),Tt.value?(u(),nt(G(yt),{key:0,data:Tt.value,options:xt},null,8,["data"])):(u(),p("div",ge,"Memuat data chart..."))]),t("div",_e,[a[25]||(a[25]=t("h4",null,"Distribusi Siswa per Kelas",-1)),Mt.value?(u(),nt(G(kt),{key:0,data:Mt.value,options:vt},null,8,["data"])):(u(),p("div",be,"Memuat data chart..."))])])),b.value?(u(),p("div",ye,[(N=e.value.period)!=null&&N.month&&((M=e.value.period)!=null&&M.year)?(u(),p("p",ke," Periode: "+s(St(e.value.period.month))+" "+s(e.value.period.year),1)):K("",!0),t("table",we,[a[27]||(a[27]=t("thead",null,[t("tr",null,[t("th",{rowspan:"2",class:"col-kls"},"Kls"),t("th",{rowspan:"2",class:"col-romb"},"Jumlah Rombel"),t("th",{colspan:"3"},"Jumlah Awal"),t("th",{colspan:"3"},"Siswa Keluar"),t("th",{colspan:"3"},"Siswa Masuk"),t("th",{colspan:"3"},"Jumlah Akhir")]),t("tr",null,[t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(ta.value,n=>(u(),p("tr",{key:n.grade},[t("td",null,[t("strong",null,s(n.gradeLabel),1)]),t("td",null,s(n.jml_romb),1),t("td",null,s(n.jumlah_awal.male),1),t("td",null,s(n.jumlah_awal.female),1),t("td",null,s(n.jumlah_awal.total),1),t("td",null,s(n.siswa_keluar.male||""),1),t("td",null,s(n.siswa_keluar.female||""),1),t("td",null,s(n.siswa_keluar.total||""),1),t("td",null,s(n.siswa_masuk.male||""),1),t("td",null,s(n.siswa_masuk.female||""),1),t("td",null,s(n.siswa_masuk.total||""),1),t("td",null,s(n.jumlah_akhir.male),1),t("td",null,s(n.jumlah_akhir.female),1),t("td",null,s(n.jumlah_akhir.total),1)]))),128)),(C=b.value)!=null&&C.totals?(u(),p("tr",$e,[a[26]||(a[26]=t("td",null,[t("strong",null,"Jml")],-1)),t("td",null,[t("strong",null,s(b.value.totals.jml_romb),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.total),1)])])):K("",!0)])])])):(u(),p("div",Ce,[t("table",xe,[a[29]||(a[29]=t("thead",null,[t("tr",null,[t("th",null,"Kelas"),t("th",null,"Laki-laki"),t("th",null,"Perempuan"),t("th",null,"Total")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(V.value,n=>{var j,k,E,et,st,lt;return u(),p("tr",{key:n},[t("td",null,[t("strong",null,"Kelas "+s(n),1)]),t("td",null,s(((k=(j=e.value.students)==null?void 0:j[`grade_${n}`])==null?void 0:k.male)||0),1),t("td",null,s(((et=(E=e.value.students)==null?void 0:E[`grade_${n}`])==null?void 0:et.female)||0),1),t("td",null,[t("strong",null,s(((lt=(st=e.value.students)==null?void 0:st[`grade_${n}`])==null?void 0:lt.total)||0),1)])])}),128)),t("tr",je,[a[28]||(a[28]=t("td",null,[t("strong",null,"Total")],-1)),t("td",null,[t("strong",null,s(pt.value),1)]),t("td",null,[t("strong",null,s(ht.value),1)]),t("td",null,[t("strong",null,s(ot.value),1)])])])])]))]),t("div",Se,[a[33]||(a[33]=t("h3",{class:"section-title"},"Status Siswa",-1)),t("div",Te,[t("div",Me,[a[31]||(a[31]=t("h4",null,"Distribusi Siswa per Status",-1)),Lt.value?(u(),nt(G(kt),{key:0,data:Lt.value,options:vt},null,8,["data"])):K("",!0)])]),t("div",Re,[t("table",Ae,[a[32]||(a[32]=t("thead",null,[t("tr",null,[t("th",null,"Status"),t("th",null,"Jumlah"),t("th",null,"Persentase")])],-1)),t("tbody",null,[l.status!=="total"?(u(!0),p(I,{key:0},F(e.value.students_by_status,(n,j)=>{var k;return u(),p("tr",{key:j},[t("td",null,[t("strong",null,s(j),1)]),t("td",null,s(n),1),t("td",null,s(((k=e.value.students_by_status)==null?void 0:k.total)>0?(n/e.value.students_by_status.total*100).toFixed(2):0)+"%",1)])}),128)):K("",!0)])])])]),e.value.classes_detail?(u(),p("div",Le,[a[38]||(a[38]=t("h3",{class:"section-title"},"Rombongan Belajar (Rombel)",-1)),t("div",Ke,[t("div",Pe,[a[34]||(a[34]=t("span",{class:"info-label"},"Total Rombel",-1)),t("span",De,s(e.value.classes_detail.total_rombel||0),1)]),t("div",Je,[a[35]||(a[35]=t("span",{class:"info-label"},"Rata-rata Siswa per Rombel",-1)),t("span",Ne,s(e.value.classes_detail.average_students_per_rombel||0),1)]),t("div",He,[a[36]||(a[36]=t("span",{class:"info-label"},"Rata-rata Kapasitas per Rombel",-1)),t("span",Ge,s(e.value.classes_detail.average_capacity||0),1)])]),(u(!0),p(I,null,F(e.value.classes_detail.by_grade,(n,j)=>(u(),p("div",{key:j,style:{"margin-bottom":"24px"}},[t("h4",Ve,s(j.replace("grade_","Kelas ")),1),t("div",ze,[t("table",Be,[a[37]||(a[37]=t("thead",null,[t("tr",null,[t("th",null,"Nama Rombel"),t("th",null,"Kode"),t("th",null,"Wali Kelas"),t("th",null,"Ruangan"),t("th",null,"Siswa"),t("th",null,"Kapasitas"),t("th",null,"Utilisasi")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(n,(k,E)=>(u(),p("tr",{key:E},[t("td",null,s(k.name),1),t("td",null,s(k.code||"-"),1),t("td",null,s(k.wali_kelas),1),t("td",null,s(k.room),1),t("td",null,s(k.students),1),t("td",null,s(k.capacity||"-"),1),t("td",null,[t("span",{style:da({color:k.utilization>100?"#ef4444":k.utilization>80?"#f59e0b":"#10b981"})},s(k.utilization)+"% ",5)])]))),128))])])])]))),128))])):K("",!0),t("div",Ie,[a[45]||(a[45]=t("h3",{class:"section-title"},"Data Tenaga Kependidikan",-1)),t("div",Fe,[t("div",Ee,[a[39]||(a[39]=t("h4",null,"Distribusi Tenaga Kependidikan",-1)),Rt.value?(u(),nt(G(kt),{key:0,data:Rt.value,options:vt},null,8,["data"])):K("",!0)]),t("div",Oe,[a[40]||(a[40]=t("h4",null,"Tenaga Kependidikan per Jenis Kelamin",-1)),At.value?(u(),nt(G(yt),{key:0,data:At.value,options:xt},null,8,["data"])):K("",!0)])]),t("div",Ue,[t("table",Ze,[a[44]||(a[44]=t("thead",null,[t("tr",null,[t("th",null,"Kategori"),t("th",null,"Laki-laki"),t("th",null,"Perempuan"),t("th",null,"Total")])],-1)),t("tbody",null,[t("tr",null,[a[41]||(a[41]=t("td",null,[t("strong",null,"Guru")],-1)),t("td",null,s(((f=e.value.employees)==null?void 0:f.teachers_male)||0),1),t("td",null,s(((P=e.value.employees)==null?void 0:P.teachers_female)||0),1),t("td",null,[t("strong",null,s(((L=e.value.employees)==null?void 0:L.teachers)||0),1)])]),t("tr",null,[a[42]||(a[42]=t("td",null,[t("strong",null,"Tenaga Administrasi/Staff")],-1)),t("td",null,s(((D=e.value.employees)==null?void 0:D.staff_male)||0),1),t("td",null,s(((x=e.value.employees)==null?void 0:x.staff_female)||0),1),t("td",null,[t("strong",null,s(((z=e.value.employees)==null?void 0:z.staff)||0),1)])]),t("tr",We,[a[43]||(a[43]=t("td",null,[t("strong",null,"Total Tenaga Kependidikan")],-1)),t("td",null,[t("strong",null,s(((W=e.value.employees)==null?void 0:W.male)||0),1)]),t("td",null,[t("strong",null,s(((Y=e.value.employees)==null?void 0:Y.female)||0),1)]),t("td",null,[t("strong",null,s(((X=e.value.employees)==null?void 0:X.total)||0),1)])])])])])]),t("div",Ye,[a[55]||(a[55]=t("h3",{class:"section-title"},"Sarana Prasarana",-1)),t("div",Xe,[t("div",qe,[a[48]||(a[48]=t("h4",null,"Tanah",-1)),t("div",Qe,[t("div",ts,[a[46]||(a[46]=t("span",{class:"facility-label"},"Jumlah",-1)),t("span",as,s(e.value.facilities.land.count),1)]),t("div",es,[a[47]||(a[47]=t("span",{class:"facility-label"},"Total Luas (m²)",-1)),t("span",ss,s(rt(e.value.facilities.land.total_area)),1)])])]),t("div",ls,[a[51]||(a[51]=t("h4",null,"Gedung",-1)),t("div",ns,[t("div",os,[a[49]||(a[49]=t("span",{class:"facility-label"},"Jumlah",-1)),t("span",is,s(e.value.facilities.buildings.count),1)]),t("div",rs,[a[50]||(a[50]=t("span",{class:"facility-label"},"Total Luas (m²)",-1)),t("span",ds,s(rt(e.value.facilities.buildings.total_area)),1)])])]),t("div",us,[a[54]||(a[54]=t("h4",null,"Ruangan",-1)),t("div",cs,[t("div",ms,[a[52]||(a[52]=t("span",{class:"facility-label"},"Total Ruangan",-1)),t("span",vs,s(e.value.facilities.rooms.count),1)])]),t("div",ps,[a[53]||(a[53]=t("h5",null,"Per Jenis Ruangan",-1)),t("table",hs,[t("tbody",null,[(u(!0),p(I,null,F(e.value.facilities.rooms.by_type,(n,j)=>(u(),p("tr",{key:j},[t("td",null,s(j),1),t("td",null,[t("strong",null,s(n),1)])]))),128))])])])])])]),t("div",fs,[a[62]||(a[62]=t("h3",{class:"section-title"},"Rasio dan Indikator",-1)),t("div",gs,[t("div",_s,[a[57]||(a[57]=t("div",{class:"ratio-icon",style:{background:"linear-gradient(135deg, #059669 0%, #047857 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("circle",{cx:"12",cy:"7",r:"4",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",bs,[t("div",ys,s(((q=e.value.summary)==null?void 0:q.student_teacher_ratio)||0),1),a[56]||(a[56]=t("div",{class:"ratio-label"},"Rasio Siswa : Guru",-1))])]),t("div",ks,[a[59]||(a[59]=ct('<div class="ratio-icon" style="background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" data-v-6cc9cf88><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-6cc9cf88><path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path></svg></div>',1)),t("div",ws,[t("div",$s,s(((Q=e.value.summary)==null?void 0:Q.average_students_per_class)||0),1),a[58]||(a[58]=t("div",{class:"ratio-label"},"Rata-rata Siswa per Kelas",-1))])]),(tt=e.value.summary)!=null&&tt.average_students_per_rombel?(u(),p("div",Cs,[a[61]||(a[61]=ct('<div class="ratio-icon" style="background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" data-v-6cc9cf88><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-6cc9cf88><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></circle><path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-6cc9cf88></path></svg></div>',1)),t("div",xs,[t("div",js,s(((at=e.value.summary)==null?void 0:at.average_students_per_rombel)||0),1),a[60]||(a[60]=t("div",{class:"ratio-label"},"Rata-rata Siswa per Rombel",-1))])])):K("",!0)])]),e.value.comparison?(u(),p("div",Ss,[a[64]||(a[64]=t("h3",{class:"section-title"},"Perbandingan dengan Tahun Ajaran Sebelumnya",-1)),t("div",Ts,[t("div",Ms,[a[63]||(a[63]=t("h4",null,"Perbandingan Jumlah Siswa",-1)),Xt(G(yt),{data:aa.value,options:Qt},null,8,["data"])])])])):K("",!0)])):K("",!0)])}}},Hs=ya(Rs,[["__scopeId","data-v-6cc9cf88"]]);export{Hs as default};
