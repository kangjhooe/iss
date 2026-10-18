import{r as _t,q as S,o as oa,D as p,B as u,H as t,O as bt,I as P,C as ia,P as yt,F as I,E as F,J as s,$ as Xt,R as ra,a0 as da,Y as ct,x as V,A as nt,N as ua}from"./vue-l7azJ0sv.js";import{_ as ca}from"./ListFilterBar-C_28C78U.js";import{L as ma}from"./LoadingSkeleton-UjaXGHtK.js";import{C as va,a as pa,L as ha,b as fa,A as ga,p as _a,e as ba,f as ya,g as kt,h as wt}from"./chart-5JSgZhMW.js";import{m as ka,_ as wa,s as $t,w as Ct,a as $a}from"./index-OztujqIJ.js";import"./pinia-i9jh4iEz.js";import"./vue-router-ueHWWC82.js";import"./axios-C0Zqfgkc.js";const Ca={getStatistics(mt=null,Z={}){const U=mt?`/v1/report/institution/${mt}`:"/v1/report/institution";return ka.get(U,{params:Z})}},xa={class:"report-page"},ja={class:"tab-header"},Sa=["disabled"],Ta={class:"filter-group"},Ma=["value"],Ra={class:"filter-group"},Aa=["value"],La={class:"filter-group"},Pa={key:0,class:"loading-wrap"},Ka={key:1,class:"report-content"},Da={class:"dashboard-mini"},Ja={class:"stat-card"},Na={class:"stat-content"},Ha={class:"stat-value"},Va={class:"stat-card"},Ga={class:"stat-content"},za={class:"stat-value"},Ba={class:"stat-card"},Ia={class:"stat-content"},Fa={class:"stat-value"},Ea={class:"stat-card"},Oa={class:"stat-content"},Ua={class:"stat-value"},Za={class:"section"},Wa={class:"info-grid"},Ya={class:"info-item"},Xa={class:"info-value"},qa={class:"info-item"},Qa={class:"info-value"},te={class:"info-item"},ae={class:"info-label"},ee={class:"info-value"},se={class:"info-item"},le={class:"info-value"},ne={class:"info-item"},oe={class:"info-value"},ie={class:"info-item"},re={class:"info-value"},de={class:"info-item"},ue={class:"info-label"},ce={class:"info-value"},me={class:"info-item"},ve={class:"info-label"},pe={class:"info-value"},he={class:"section"},fe={key:0,class:"chart-container"},ge={class:"chart-wrapper"},_e={key:1,class:"chart-placeholder"},be={class:"chart-wrapper"},ye={key:1,class:"chart-placeholder"},ke={key:1,class:"data-table-container"},we={key:0,class:"table-period-hint"},$e={class:"data-table data-table-student-report"},Ce={key:0,class:"total-row"},xe={key:2,class:"data-table-container"},je={class:"data-table"},Se={class:"total-row"},Te={class:"section"},Me={class:"chart-container"},Re={class:"chart-wrapper"},Ae={class:"data-table-container"},Le={class:"data-table"},Pe={key:0,class:"section"},Ke={class:"info-grid",style:{"margin-bottom":"20px"}},De={class:"info-item"},Je={class:"info-value"},Ne={class:"info-item"},He={class:"info-value"},Ve={class:"info-item"},Ge={class:"info-value"},ze={style:{"margin-bottom":"12px",color:"#475569","font-size":"16px"}},Be={class:"data-table-container"},Ie={class:"data-table"},Fe={class:"section"},Ee={class:"chart-container"},Oe={class:"chart-wrapper"},Ue={class:"chart-wrapper"},Ze={class:"data-table-container"},We={class:"data-table"},Ye={class:"total-row"},Xe={class:"section"},qe={class:"facilities-grid"},Qe={class:"facility-card"},ts={class:"facility-stats"},as={class:"facility-stat"},es={class:"facility-value"},ss={class:"facility-stat"},ls={class:"facility-value"},ns={class:"facility-card"},os={class:"facility-stats"},is={class:"facility-stat"},rs={class:"facility-value"},ds={class:"facility-stat"},us={class:"facility-value"},cs={class:"facility-card"},ms={class:"facility-stats"},vs={class:"facility-stat"},ps={class:"facility-value"},hs={class:"rooms-by-type"},fs={class:"rooms-table"},gs={class:"section"},_s={class:"ratios-grid"},bs={class:"ratio-card"},ys={class:"ratio-content"},ks={class:"ratio-value"},ws={class:"ratio-card"},$s={class:"ratio-content"},Cs={class:"ratio-value"},xs={key:0,class:"ratio-card"},js={class:"ratio-content"},Ss={class:"ratio-value"},Ts={key:1,class:"section"},Ms={class:"comparison-container"},Rs={class:"comparison-chart"},As={__name:"Report",setup(mt){va.register(pa,ha,fa,ga,_a,ba,ya);const Z=$a(),U=_t(!1),e=_t(null),$=_t({month:(new Date().getMonth()+1).toString(),year:new Date().getFullYear().toString(),compareWithPrevious:!1}),xt=[{value:"1",label:"Januari"},{value:"2",label:"Februari"},{value:"3",label:"Maret"},{value:"4",label:"April"},{value:"5",label:"Mei"},{value:"6",label:"Juni"},{value:"7",label:"Juli"},{value:"8",label:"Agustus"},{value:"9",label:"September"},{value:"10",label:"Oktober"},{value:"11",label:"November"},{value:"12",label:"Desember"}],qt=S(()=>{const l=new Date().getFullYear(),a=[];for(let i=l;i>=l-5;i--)a.push(i.toString());return a}),jt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"top"},title:{display:!1}}},vt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"bottom"}}},Qt={responsive:!0,maintainAspectRatio:!1,plugins:{legend:{position:"top"}},scales:{y:{beginAtZero:!0}}},G=S(()=>{var i,v;if(!((v=(i=e.value)==null?void 0:i.institution)!=null&&v.level))return[7,8,9];const l=e.value.institution.level;return{TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[l]||[7,8,9]}),St=l=>{const a=Number(l);return a===10?"X":a===11?"XI":a===12?"XII":String(l)},b=S(()=>{var N,M,C,f,K;const l=(N=e.value)==null?void 0:N.students_table_detail;if(l!=null&&l.by_grade)return l;const a=(M=e.value)==null?void 0:M.students,i=(C=e.value)==null?void 0:C.classes_detail;if(!a||!((K=(f=e.value)==null?void 0:f.institution)!=null&&K.level))return null;const v=e.value.institution.level,o={TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[v]||[7,8,9],c={};let r=0;const y={jml_romb:0,jumlah_awal:{male:0,female:0,total:0},siswa_keluar:{male:0,female:0,total:0},siswa_masuk:{male:0,female:0,total:0},jumlah_akhir:{male:0,female:0,total:0}};for(const L of o){const D=`grade_${L}`,x=a[D]||{male:0,female:0,total:0},z=i!=null&&i.by_grade&&Array.isArray(i.by_grade[D])?i.by_grade[D].length:0;c[D]={grade:L,jml_romb:z,jumlah_awal:{...x},siswa_keluar:{male:0,female:0,total:0},siswa_masuk:{male:0,female:0,total:0},jumlah_akhir:{...x}},r+=z,y.jumlah_awal.male+=x.male,y.jumlah_awal.female+=x.female,y.jumlah_awal.total+=x.total,y.siswa_keluar.total+=0,y.siswa_masuk.total+=0,y.jumlah_akhir.male+=x.male,y.jumlah_akhir.female+=x.female,y.jumlah_akhir.total+=x.total}return y.jml_romb=r,{grade_range:[o[0],o[o.length-1]],by_grade:c,totals:y}}),ta=S(()=>{const l=b.value;if(!(l!=null&&l.by_grade))return[];const[a,i]=l.grade_range||[1,12],v=[];for(let d=a;d<=i;d++){const o=`grade_${d}`,c=l.by_grade[o];c&&v.push({grade:d,gradeLabel:St(d),jml_romb:c.jml_romb??0,jumlah_awal:c.jumlah_awal??{male:0,female:0,total:0},siswa_keluar:c.siswa_keluar??{male:0,female:0,total:0},siswa_masuk:c.siswa_masuk??{male:0,female:0,total:0},jumlah_akhir:c.jumlah_akhir??{male:0,female:0,total:0}})}return v}),Tt=l=>["","Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"][Number(l)]||l,Mt=S(()=>{if(!e.value||!e.value.students)return null;const l=G.value,a=l.map(d=>`Kelas ${d}`),i=l.map(d=>{var o;return((o=e.value.students[`grade_${d}`])==null?void 0:o.male)||0}),v=l.map(d=>{var o;return((o=e.value.students[`grade_${d}`])==null?void 0:o.female)||0});return{labels:a,datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:i},{label:"Perempuan",backgroundColor:"#f093fb",data:v}]}}),Rt=S(()=>{if(!e.value||!e.value.students)return null;const l=G.value;return{labels:l.map(v=>`Kelas ${v}`),datasets:[{backgroundColor:["#059669","#f093fb","#4facfe","#43e97b","#38f9d7","#f5576c","#047857","#059669","#f093fb","#4facfe","#43e97b","#38f9d7"].slice(0,l.length),data:l.map(v=>{var d;return((d=e.value.students[`grade_${v}`])==null?void 0:d.total)||0})}]}});S(()=>{var i,v,d,o;if(!e.value)return null;const l=((i=e.value.employees)==null?void 0:i.teachers_male)||((v=e.value.teachers)==null?void 0:v.male)||0,a=((d=e.value.employees)==null?void 0:d.teachers_female)||((o=e.value.teachers)==null?void 0:o.female)||0;return{labels:["Guru"],datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:[l]},{label:"Perempuan",backgroundColor:"#f093fb",data:[a]}]}});const At=S(()=>{if(!e.value||!e.value.employees)return null;const l=e.value.employees.teachers||0,a=e.value.employees.staff||0;return{labels:["Guru","Tenaga Administrasi/Staff"],datasets:[{backgroundColor:["#059669","#f093fb"],data:[l,a]}]}}),Lt=S(()=>{if(!e.value||!e.value.employees)return null;const l=e.value.employees.male||0,a=e.value.employees.female||0;return{labels:["Tenaga Kependidikan"],datasets:[{label:"Laki-laki",backgroundColor:"#059669",data:[l]},{label:"Perempuan",backgroundColor:"#f093fb",data:[a]}]}}),Pt=S(()=>{if(!e.value||!e.value.students_by_status)return null;const l=e.value.students_by_status,a=[],i=[],v=["#10b981","#059669","#f59e0b","#ef4444","#94a3b8"];for(const[d,o]of Object.entries(l))d!=="total"&&o>0&&(a.push(d),i.push(o));return{labels:a,datasets:[{backgroundColor:v.slice(0,a.length),data:i}]}}),aa=S(()=>{var d;if(!e.value||!e.value.comparison||!e.value.students)return null;const l=e.value.students,a=e.value.comparison.students,i=G.value;return{labels:i.map(o=>`Kelas ${o}`),datasets:[{label:((d=e.value.academic_year)==null?void 0:d.name)||"Tahun Ajaran Aktif",backgroundColor:"#059669",data:i.map(o=>{var c;return((c=l[`grade_${o}`])==null?void 0:c.total)||0})},{label:e.value.comparison.academic_year||"Tahun Ajaran Sebelumnya",backgroundColor:"#94a3b8",data:i.map(o=>{var c;return((c=a[`grade_${o}`])==null?void 0:c.total)||0})}]}}),pt=S(()=>!e.value||!e.value.students?0:G.value.reduce((l,a)=>{var i;return l+(((i=e.value.students[`grade_${a}`])==null?void 0:i.male)||0)},0)),ht=S(()=>!e.value||!e.value.students?0:G.value.reduce((l,a)=>{var i;return l+(((i=e.value.students[`grade_${a}`])==null?void 0:i.female)||0)},0)),ot=S(()=>!e.value||!e.value.students?0:pt.value+ht.value),it=async()=>{var l,a,i,v,d;U.value=!0;try{const o={};$.value.month&&(o.month=$.value.month),$.value.year&&(o.year=$.value.year),$.value.compareWithPrevious&&(o.compare_with_previous=!0);const c=await Ca.getStatistics(null,o);if(c.data&&c.data.data){const r=c.data.data,y=(l=r.institution)==null?void 0:l.level,M={TK:[1],PAUD:[1],SD:[1,2,3,4,5,6],MI:[1,2,3,4,5,6],SMP:[7,8,9],MTs:[7,8,9],SMA:[10,11,12],MA:[10,11,12],SMK:[10,11,12],MAK:[10,11,12]}[y]||[7,8,9];if(r.students?M.forEach(C=>{const f=`grade_${C}`;r.students[f]?(r.students[f].male=Number(r.students[f].male)||0,r.students[f].female=Number(r.students[f].female)||0,r.students[f].total=r.students[f].male+r.students[f].female):r.students[f]={male:0,female:0,total:0}}):(r.students={},M.forEach(C=>{r.students[`grade_${C}`]={male:0,female:0,total:0}})),r.summary){const C=M.reduce((f,K)=>{var L;return f+(((L=r.students[`grade_${K}`])==null?void 0:L.total)||0)},0);r.summary.total_students=C}r.employees&&!r.teachers?r.teachers={male:r.employees.teachers_male||0,female:r.employees.teachers_female||0,total:r.employees.teachers||0}:r.teachers||(r.teachers={male:0,female:0,total:0}),r.students_by_status||(r.students_by_status={Aktif:0,Lulus:0,Pindah:0,"Drop Out":0,Lainnya:0,total:0}),r.classes_detail||(r.classes_detail={by_grade:{},total_rombel:0,total_capacity:0,total_students:0,average_capacity:0,average_students_per_rombel:0}),e.value=r}else throw new Error("Format data tidak valid")}catch(o){let c="Gagal memuat data laporan";(i=(a=o.response)==null?void 0:a.data)!=null&&i.message?c=o.response.data.message:(d=(v=o.response)==null?void 0:v.data)!=null&&d.error?c=o.response.data.error:o.formattedMessage?c=o.formattedMessage:o.message&&(c=o.message),Z.error("Gagal",c)}finally{U.value=!1}},T=l=>String(l??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#39;"),Kt=l=>[l.address,l.village?`Desa/Kel. ${l.village}`:"",l.sub_district?`Kec. ${l.sub_district}`:"",l.district,l.province,l.postal_code].filter(Boolean).join(", ")||"-",rt=l=>l?new Intl.NumberFormat("id-ID").format(l):"0",ea=l=>Ct(l==null?void 0:l.level),sa=async()=>{var l,a,i,v,d,o,c,r,y,N,M,C,f,K,L,D,x,z,W,Y,X,q,Q,tt,at,n,j,k,E,et,st,lt,Dt,Jt,Nt,Ht,Vt;if(!e.value){Z.error("Gagal","Tidak ada data untuk diekspor");return}try{const m=e.value.institution,dt=window.open("","_blank"),la=`Laporan_${m.name}_${new Date().toISOString().split("T")[0]}.pdf`,Gt=Kt(m),zt=ea(m),H=b.value||e.value.students_table_detail;let ft;if(H!=null&&H.by_grade&&(H!=null&&H.totals)){const[w,R]=H.grade_range||[1,12],g=h=>{var A,J,ut,Bt,It,Ft,Et,Ot,Ut,Zt,Wt,Yt;return[((A=h.jumlah_awal)==null?void 0:A.male)??0,((J=h.jumlah_awal)==null?void 0:J.female)??0,((ut=h.jumlah_awal)==null?void 0:ut.total)??0,((Bt=h.siswa_keluar)==null?void 0:Bt.male)??"",((It=h.siswa_keluar)==null?void 0:It.female)??"",((Ft=h.siswa_keluar)==null?void 0:Ft.total)??"",((Et=h.siswa_masuk)==null?void 0:Et.male)??"",((Ot=h.siswa_masuk)==null?void 0:Ot.female)??"",((Ut=h.siswa_masuk)==null?void 0:Ut.total)??"",((Zt=h.jumlah_akhir)==null?void 0:Zt.male)??0,((Wt=h.jumlah_akhir)==null?void 0:Wt.female)??0,((Yt=h.jumlah_akhir)==null?void 0:Yt.total)??0]},B=[];for(let h=w;h<=R;h++){const A=H.by_grade[`grade_${h}`];if(!A)continue;const J=g(A);B.push(`<tr><td><strong>${St(h)}</strong></td><td>${A.jml_romb??0}</td>${J.map(ut=>`<td>${ut}</td>`).join("")}</tr>`)}const _=H.totals,O=[((l=_.jumlah_awal)==null?void 0:l.male)??0,((a=_.jumlah_awal)==null?void 0:a.female)??0,((i=_.jumlah_awal)==null?void 0:i.total)??0,((v=_.siswa_keluar)==null?void 0:v.male)??0,((d=_.siswa_keluar)==null?void 0:d.female)??0,((o=_.siswa_keluar)==null?void 0:o.total)??0,((c=_.siswa_masuk)==null?void 0:c.male)??0,((r=_.siswa_masuk)==null?void 0:r.female)??0,((y=_.siswa_masuk)==null?void 0:y.total)??0,((N=_.jumlah_akhir)==null?void 0:N.male)??0,((M=_.jumlah_akhir)==null?void 0:M.female)??0,((C=_.jumlah_akhir)==null?void 0:C.total)??0].map(h=>`<td><strong>${h}</strong></td>`).join("");ft=`
        <p class="table-period-hint">Periode: ${Tt(((f=e.value.period)==null?void 0:f.month)??$.value.month)} ${((K=e.value.period)==null?void 0:K.year)||$.value.year||""}</p>
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
            ${G.value.map(g=>{var B,_,O,h,A,J;return`
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
                ${m.nss?` · ${$t(m.level)}: ${T(m.nss)}`:""}
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
          <p>Periode: ${$.value.month?(x=xt.find(w=>w.value===$.value.month))==null?void 0:x.label:"Semua"} ${$.value.year||""}</p>
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
              <span class="info-label">${$t(m.level)}</span>
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
              <span class="info-label">${zt}</span>
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
              <span class="info-value">${((Dt=e.value.summary)==null?void 0:Dt.student_teacher_ratio)||0}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Rata-rata Siswa per Kelas</span>
              <span class="info-value">${((Jt=e.value.summary)==null?void 0:Jt.average_students_per_class)||0}</span>
            </div>
            ${(Nt=e.value.summary)!=null&&Nt.average_students_per_rombel?`
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
          <p><strong>Tahun Ajaran Aktif:</strong> ${((Ht=e.value.academic_year)==null?void 0:Ht.name)||"-"}</p>
          <p><strong>Tahun Ajaran Sebelumnya:</strong> ${e.value.comparison.academic_year}</p>
          <table class="data-table">
            <thead>
              <tr>
                <th>Kelas</th>
                <th>${((Vt=e.value.academic_year)==null?void 0:Vt.name)||"Tahun Aktif"}</th>
                <th>${e.value.comparison.academic_year}</th>
                <th>Selisih</th>
              </tr>
            </thead>
            <tbody>
              ${G.value.map(w=>{var R,g,B,_,O,h,A,J;return`
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
              <div class="footer-signature-label">${T(zt)}</div>
              <div class="footer-signature-name">${T(m.principal_name||"___________________")}</div>
              <div class="footer-signature-nip">NIP. ${T(m.principal_nip||"___________________")}</div>
            </div>
          </div>
        </div>
      </body>
      </html>
    `;dt.document.write(na),dt.document.close(),setTimeout(()=>{dt.print(),dt.document.title=la},250)}catch{Z.error("Gagal","Gagal mengekspor PDF")}};return oa(()=>{it()}),(l,a)=>{var i,v,d,o,c,r,y,N,M,C,f,K,L,D,x,z,W,Y,X,q,Q,tt,at;return u(),p("div",xa,[t("div",ja,[a[4]||(a[4]=t("div",{class:"filters filters-inline"},null,-1)),t("button",{onClick:sa,class:"btn-primary btn-compact",disabled:U.value},[...a[3]||(a[3]=[t("svg",{width:"20",height:"20",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M6 9V2H18V9",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("path",{d:"M6 18H4C3.46957 18 2.96086 17.7893 2.58579 17.4142C2.21071 17.0391 2 16.5304 2 16V11C2 10.4696 2.21071 9.96086 2.58579 9.58579C2.96086 9.21071 3.46957 9 4 9H20C20.5304 9 21.0391 9.21071 21.4142 9.58579C21.7893 9.96086 22 10.4696 22 11V16C22 16.5304 21.7893 17.0391 21.4142 17.4142C21.0391 17.7893 20.5304 18 20 18H18",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("path",{d:"M18 14H6V22H18V14Z",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})],-1),t("span",null,"Export PDF",-1)])],8,Sa)]),bt(ca,{class:"filters filters-inline"},{default:ia(()=>[t("div",Ta,[a[6]||(a[6]=t("label",null,"Bulan",-1)),yt(t("select",{"onUpdate:modelValue":a[0]||(a[0]=n=>$.value.month=n),onChange:it,class:"filter-select"},[a[5]||(a[5]=t("option",{value:""},"Semua Bulan",-1)),(u(),p(I,null,F(xt,n=>t("option",{key:n.value,value:n.value},s(n.label),9,Ma)),64))],544),[[Xt,$.value.month]])]),t("div",Ra,[a[8]||(a[8]=t("label",null,"Tahun",-1)),yt(t("select",{"onUpdate:modelValue":a[1]||(a[1]=n=>$.value.year=n),onChange:it,class:"filter-select"},[a[7]||(a[7]=t("option",{value:""},"Semua Tahun",-1)),(u(!0),p(I,null,F(qt.value,n=>(u(),p("option",{key:n,value:n},s(n),9,Aa))),128))],544),[[Xt,$.value.year]])]),t("div",La,[t("label",null,[yt(t("input",{type:"checkbox","onUpdate:modelValue":a[2]||(a[2]=n=>$.value.compareWithPrevious=n),onChange:it},null,544),[[da,$.value.compareWithPrevious]]),a[9]||(a[9]=ra(" Bandingkan dengan Tahun Ajaran Sebelumnya ",-1))])])]),_:1}),U.value?(u(),p("div",Pa,[bt(ma,{type:"card",lines:6,"line-widths":["100%","80%","60%","100%","70%","50%"]})])):e.value?(u(),p("div",Ka,[t("div",Da,[t("div",Ja,[a[11]||(a[11]=t("div",{class:"stat-icon",style:{background:"linear-gradient(135deg, #059669 0%, #047857 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("circle",{cx:"12",cy:"7",r:"4",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",Na,[t("div",Ha,s(((i=e.value.summary)==null?void 0:i.total_students)??ot.value),1),a[10]||(a[10]=t("div",{class:"stat-label"},"Total Siswa",-1))])]),t("div",Va,[a[13]||(a[13]=ct('<div class="stat-icon" style="background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" data-v-3487442b><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-3487442b><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></circle><path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path></svg></div>',1)),t("div",Ga,[t("div",za,s(((v=e.value.summary)==null?void 0:v.total_teachers)||0),1),a[12]||(a[12]=t("div",{class:"stat-label"},"Total Guru",-1))])]),t("div",Ba,[a[15]||(a[15]=ct('<div class="stat-icon" style="background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" data-v-3487442b><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-3487442b><path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path></svg></div>',1)),t("div",Ia,[t("div",Fa,s(((d=e.value.summary)==null?void 0:d.total_classes)||0),1),a[14]||(a[14]=t("div",{class:"stat-label"},"Total Kelas",-1))])]),t("div",Ea,[a[17]||(a[17]=t("div",{class:"stat-icon",style:{background:"linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5304 5.21071 21.0391 5.58579 21.4142C5.96086 21.7893 6.46957 22 7 22H17C17.5304 22 18.0391 21.7893 18.4142 21.4142C18.7893 21.0391 19 20.5304 19 20V10M19 10L21 12M19 10L12 3L5 10",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",Oa,[t("div",Ua,s(((o=e.value.summary)==null?void 0:o.total_facilities)||0),1),a[16]||(a[16]=t("div",{class:"stat-label"},"Sarana Prasarana",-1))])])]),t("div",Za,[a[23]||(a[23]=t("h3",{class:"section-title"},"Identitas Lembaga",-1)),t("div",Wa,[t("div",Ya,[a[18]||(a[18]=t("span",{class:"info-label"},"Nama Lembaga",-1)),t("span",Xa,s(e.value.institution.name),1)]),t("div",qa,[a[19]||(a[19]=t("span",{class:"info-label"},"NPSN",-1)),t("span",Qa,s(e.value.institution.npsn||"-"),1)]),t("div",te,[t("span",ae,s(V($t)((c=e.value.institution)==null?void 0:c.level)),1),t("span",ee,s(e.value.institution.nss||"-"),1)]),t("div",se,[a[20]||(a[20]=t("span",{class:"info-label"},"Level",-1)),t("span",le,s(e.value.institution.level||"-"),1)]),t("div",ne,[a[21]||(a[21]=t("span",{class:"info-label"},"Tipe",-1)),t("span",oe,s(e.value.institution.type||"-"),1)]),t("div",ie,[a[22]||(a[22]=t("span",{class:"info-label"},"Alamat",-1)),t("span",re,s(Kt(e.value.institution)),1)]),t("div",de,[t("span",ue,s(V(Ct)((r=e.value.institution)==null?void 0:r.level)),1),t("span",ce,s(e.value.institution.principal_name||"-"),1)]),t("div",me,[t("span",ve,"NIP "+s(V(Ct)((y=e.value.institution)==null?void 0:y.level)),1),t("span",pe,s(e.value.institution.principal_nip||"-"),1)])])]),t("div",he,[a[30]||(a[30]=t("h3",{class:"section-title"},"Data Siswa",-1)),b.value?P("",!0):(u(),p("div",fe,[t("div",ge,[a[24]||(a[24]=t("h4",null,"Jumlah Siswa per Kelas",-1)),Mt.value?(u(),nt(V(kt),{key:0,data:Mt.value,options:jt},null,8,["data"])):(u(),p("div",_e,"Memuat data chart..."))]),t("div",be,[a[25]||(a[25]=t("h4",null,"Distribusi Siswa per Kelas",-1)),Rt.value?(u(),nt(V(wt),{key:0,data:Rt.value,options:vt},null,8,["data"])):(u(),p("div",ye,"Memuat data chart..."))])])),b.value?(u(),p("div",ke,[(N=e.value.period)!=null&&N.month&&((M=e.value.period)!=null&&M.year)?(u(),p("p",we," Periode: "+s(Tt(e.value.period.month))+" "+s(e.value.period.year),1)):P("",!0),t("table",$e,[a[27]||(a[27]=t("thead",null,[t("tr",null,[t("th",{rowspan:"2",class:"col-kls"},"Kls"),t("th",{rowspan:"2",class:"col-romb"},"Jumlah Rombel"),t("th",{colspan:"3"},"Jumlah Awal"),t("th",{colspan:"3"},"Siswa Keluar"),t("th",{colspan:"3"},"Siswa Masuk"),t("th",{colspan:"3"},"Jumlah Akhir")]),t("tr",null,[t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml"),t("th",null,"L"),t("th",null,"P"),t("th",null,"Jml")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(ta.value,n=>(u(),p("tr",{key:n.grade},[t("td",null,[t("strong",null,s(n.gradeLabel),1)]),t("td",null,s(n.jml_romb),1),t("td",null,s(n.jumlah_awal.male),1),t("td",null,s(n.jumlah_awal.female),1),t("td",null,s(n.jumlah_awal.total),1),t("td",null,s(n.siswa_keluar.male||""),1),t("td",null,s(n.siswa_keluar.female||""),1),t("td",null,s(n.siswa_keluar.total||""),1),t("td",null,s(n.siswa_masuk.male||""),1),t("td",null,s(n.siswa_masuk.female||""),1),t("td",null,s(n.siswa_masuk.total||""),1),t("td",null,s(n.jumlah_akhir.male),1),t("td",null,s(n.jumlah_akhir.female),1),t("td",null,s(n.jumlah_akhir.total),1)]))),128)),(C=b.value)!=null&&C.totals?(u(),p("tr",Ce,[a[26]||(a[26]=t("td",null,[t("strong",null,"Jml")],-1)),t("td",null,[t("strong",null,s(b.value.totals.jml_romb),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_awal.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_keluar.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.siswa_masuk.total),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.male),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.female),1)]),t("td",null,[t("strong",null,s(b.value.totals.jumlah_akhir.total),1)])])):P("",!0)])])])):(u(),p("div",xe,[t("table",je,[a[29]||(a[29]=t("thead",null,[t("tr",null,[t("th",null,"Kelas"),t("th",null,"Laki-laki"),t("th",null,"Perempuan"),t("th",null,"Total")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(G.value,n=>{var j,k,E,et,st,lt;return u(),p("tr",{key:n},[t("td",null,[t("strong",null,"Kelas "+s(n),1)]),t("td",null,s(((k=(j=e.value.students)==null?void 0:j[`grade_${n}`])==null?void 0:k.male)||0),1),t("td",null,s(((et=(E=e.value.students)==null?void 0:E[`grade_${n}`])==null?void 0:et.female)||0),1),t("td",null,[t("strong",null,s(((lt=(st=e.value.students)==null?void 0:st[`grade_${n}`])==null?void 0:lt.total)||0),1)])])}),128)),t("tr",Se,[a[28]||(a[28]=t("td",null,[t("strong",null,"Total")],-1)),t("td",null,[t("strong",null,s(pt.value),1)]),t("td",null,[t("strong",null,s(ht.value),1)]),t("td",null,[t("strong",null,s(ot.value),1)])])])])]))]),t("div",Te,[a[33]||(a[33]=t("h3",{class:"section-title"},"Status Siswa",-1)),t("div",Me,[t("div",Re,[a[31]||(a[31]=t("h4",null,"Distribusi Siswa per Status",-1)),Pt.value?(u(),nt(V(wt),{key:0,data:Pt.value,options:vt},null,8,["data"])):P("",!0)])]),t("div",Ae,[t("table",Le,[a[32]||(a[32]=t("thead",null,[t("tr",null,[t("th",null,"Status"),t("th",null,"Jumlah"),t("th",null,"Persentase")])],-1)),t("tbody",null,[l.status!=="total"?(u(!0),p(I,{key:0},F(e.value.students_by_status,(n,j)=>{var k;return u(),p("tr",{key:j},[t("td",null,[t("strong",null,s(j),1)]),t("td",null,s(n),1),t("td",null,s(((k=e.value.students_by_status)==null?void 0:k.total)>0?(n/e.value.students_by_status.total*100).toFixed(2):0)+"%",1)])}),128)):P("",!0)])])])]),e.value.classes_detail?(u(),p("div",Pe,[a[38]||(a[38]=t("h3",{class:"section-title"},"Rombongan Belajar (Rombel)",-1)),t("div",Ke,[t("div",De,[a[34]||(a[34]=t("span",{class:"info-label"},"Total Rombel",-1)),t("span",Je,s(e.value.classes_detail.total_rombel||0),1)]),t("div",Ne,[a[35]||(a[35]=t("span",{class:"info-label"},"Rata-rata Siswa per Rombel",-1)),t("span",He,s(e.value.classes_detail.average_students_per_rombel||0),1)]),t("div",Ve,[a[36]||(a[36]=t("span",{class:"info-label"},"Rata-rata Kapasitas per Rombel",-1)),t("span",Ge,s(e.value.classes_detail.average_capacity||0),1)])]),(u(!0),p(I,null,F(e.value.classes_detail.by_grade,(n,j)=>(u(),p("div",{key:j,style:{"margin-bottom":"24px"}},[t("h4",ze,s(j.replace("grade_","Kelas ")),1),t("div",Be,[t("table",Ie,[a[37]||(a[37]=t("thead",null,[t("tr",null,[t("th",null,"Nama Rombel"),t("th",null,"Kode"),t("th",null,"Wali Kelas"),t("th",null,"Ruangan"),t("th",null,"Siswa"),t("th",null,"Kapasitas"),t("th",null,"Utilisasi")])],-1)),t("tbody",null,[(u(!0),p(I,null,F(n,(k,E)=>(u(),p("tr",{key:E},[t("td",null,s(k.name),1),t("td",null,s(k.code||"-"),1),t("td",null,s(k.wali_kelas),1),t("td",null,s(k.room),1),t("td",null,s(k.students),1),t("td",null,s(k.capacity||"-"),1),t("td",null,[t("span",{style:ua({color:k.utilization>100?"#ef4444":k.utilization>80?"#f59e0b":"#10b981"})},s(k.utilization)+"% ",5)])]))),128))])])])]))),128))])):P("",!0),t("div",Fe,[a[45]||(a[45]=t("h3",{class:"section-title"},"Data Tenaga Kependidikan",-1)),t("div",Ee,[t("div",Oe,[a[39]||(a[39]=t("h4",null,"Distribusi Tenaga Kependidikan",-1)),At.value?(u(),nt(V(wt),{key:0,data:At.value,options:vt},null,8,["data"])):P("",!0)]),t("div",Ue,[a[40]||(a[40]=t("h4",null,"Tenaga Kependidikan per Jenis Kelamin",-1)),Lt.value?(u(),nt(V(kt),{key:0,data:Lt.value,options:jt},null,8,["data"])):P("",!0)])]),t("div",Ze,[t("table",We,[a[44]||(a[44]=t("thead",null,[t("tr",null,[t("th",null,"Kategori"),t("th",null,"Laki-laki"),t("th",null,"Perempuan"),t("th",null,"Total")])],-1)),t("tbody",null,[t("tr",null,[a[41]||(a[41]=t("td",null,[t("strong",null,"Guru")],-1)),t("td",null,s(((f=e.value.employees)==null?void 0:f.teachers_male)||0),1),t("td",null,s(((K=e.value.employees)==null?void 0:K.teachers_female)||0),1),t("td",null,[t("strong",null,s(((L=e.value.employees)==null?void 0:L.teachers)||0),1)])]),t("tr",null,[a[42]||(a[42]=t("td",null,[t("strong",null,"Tenaga Administrasi/Staff")],-1)),t("td",null,s(((D=e.value.employees)==null?void 0:D.staff_male)||0),1),t("td",null,s(((x=e.value.employees)==null?void 0:x.staff_female)||0),1),t("td",null,[t("strong",null,s(((z=e.value.employees)==null?void 0:z.staff)||0),1)])]),t("tr",Ye,[a[43]||(a[43]=t("td",null,[t("strong",null,"Total Tenaga Kependidikan")],-1)),t("td",null,[t("strong",null,s(((W=e.value.employees)==null?void 0:W.male)||0),1)]),t("td",null,[t("strong",null,s(((Y=e.value.employees)==null?void 0:Y.female)||0),1)]),t("td",null,[t("strong",null,s(((X=e.value.employees)==null?void 0:X.total)||0),1)])])])])])]),t("div",Xe,[a[55]||(a[55]=t("h3",{class:"section-title"},"Sarana Prasarana",-1)),t("div",qe,[t("div",Qe,[a[48]||(a[48]=t("h4",null,"Tanah",-1)),t("div",ts,[t("div",as,[a[46]||(a[46]=t("span",{class:"facility-label"},"Jumlah",-1)),t("span",es,s(e.value.facilities.land.count),1)]),t("div",ss,[a[47]||(a[47]=t("span",{class:"facility-label"},"Total Luas (m²)",-1)),t("span",ls,s(rt(e.value.facilities.land.total_area)),1)])])]),t("div",ns,[a[51]||(a[51]=t("h4",null,"Gedung",-1)),t("div",os,[t("div",is,[a[49]||(a[49]=t("span",{class:"facility-label"},"Jumlah",-1)),t("span",rs,s(e.value.facilities.buildings.count),1)]),t("div",ds,[a[50]||(a[50]=t("span",{class:"facility-label"},"Total Luas (m²)",-1)),t("span",us,s(rt(e.value.facilities.buildings.total_area)),1)])])]),t("div",cs,[a[54]||(a[54]=t("h4",null,"Ruangan",-1)),t("div",ms,[t("div",vs,[a[52]||(a[52]=t("span",{class:"facility-label"},"Total Ruangan",-1)),t("span",ps,s(e.value.facilities.rooms.count),1)])]),t("div",hs,[a[53]||(a[53]=t("h5",null,"Per Jenis Ruangan",-1)),t("table",fs,[t("tbody",null,[(u(!0),p(I,null,F(e.value.facilities.rooms.by_type,(n,j)=>(u(),p("tr",{key:j},[t("td",null,s(j),1),t("td",null,[t("strong",null,s(n),1)])]))),128))])])])])])]),t("div",gs,[a[62]||(a[62]=t("h3",{class:"section-title"},"Rasio dan Indikator",-1)),t("div",_s,[t("div",bs,[a[57]||(a[57]=t("div",{class:"ratio-icon",style:{background:"linear-gradient(135deg, #059669 0%, #047857 100%)"}},[t("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},[t("path",{d:"M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"}),t("circle",{cx:"12",cy:"7",r:"4",stroke:"currentColor","stroke-width":"2","stroke-linecap":"round","stroke-linejoin":"round"})])],-1)),t("div",ys,[t("div",ks,s(((q=e.value.summary)==null?void 0:q.student_teacher_ratio)||0),1),a[56]||(a[56]=t("div",{class:"ratio-label"},"Rasio Siswa : Guru",-1))])]),t("div",ws,[a[59]||(a[59]=ct('<div class="ratio-icon" style="background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" data-v-3487442b><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-3487442b><path d="M4 19.5C4 18.6716 4.67157 18 5.5 18H18.5C19.3284 18 20 18.6716 20 19.5C20 20.3284 19.3284 21 18.5 21H5.5C4.67157 21 4 20.3284 4 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M4 4.5C4 3.67157 4.67157 3 5.5 3H18.5C19.3284 3 20 3.67157 20 4.5C20 5.32843 19.3284 6 18.5 6H5.5C4.67157 6 4 5.32843 4 4.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M4 12C4 11.1716 4.67157 10.5 5.5 10.5H18.5C19.3284 10.5 20 11.1716 20 12C20 12.8284 19.3284 13.5 18.5 13.5H5.5C4.67157 13.5 4 12.8284 4 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path></svg></div>',1)),t("div",$s,[t("div",Cs,s(((Q=e.value.summary)==null?void 0:Q.average_students_per_class)||0),1),a[58]||(a[58]=t("div",{class:"ratio-label"},"Rata-rata Siswa per Kelas",-1))])]),(tt=e.value.summary)!=null&&tt.average_students_per_rombel?(u(),p("div",xs,[a[61]||(a[61]=ct('<div class="ratio-icon" style="background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" data-v-3487442b><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-v-3487442b><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></circle><path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path><path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-v-3487442b></path></svg></div>',1)),t("div",js,[t("div",Ss,s(((at=e.value.summary)==null?void 0:at.average_students_per_rombel)||0),1),a[60]||(a[60]=t("div",{class:"ratio-label"},"Rata-rata Siswa per Rombel",-1))])])):P("",!0)])]),e.value.comparison?(u(),p("div",Ts,[a[64]||(a[64]=t("h3",{class:"section-title"},"Perbandingan dengan Tahun Ajaran Sebelumnya",-1)),t("div",Ms,[t("div",Rs,[a[63]||(a[63]=t("h4",null,"Perbandingan Jumlah Siswa",-1)),bt(V(kt),{data:aa.value,options:Qt},null,8,["data"])])])])):P("",!0)])):P("",!0)])}}},Gs=wa(As,[["__scopeId","data-v-3487442b"]]);export{Gs as default};
