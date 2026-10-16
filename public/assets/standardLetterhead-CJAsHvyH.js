import{s as d}from"./index-DhDM2Vtw.js";function e(t){return String(t??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;")}function i(t){if(!t)return"";const a=[t.address,t.village?`Desa/Kel. ${t.village}`:null,t.sub_district?`Kec. ${t.sub_district}`:null,t.district,t.province,t.postal_code].filter(Boolean).join(", ");return a||t.full_address||""}function l(t){if(!t)return"NPSN: -";const a=[`NPSN: ${t.npsn||"-"}`];return t.nss&&a.push(`${d(t.level)}: ${t.nss}`),t.phone&&a.push(`Telp: ${t.phone}`),t.email&&a.push(`Email: ${t.email}`),t.website&&a.push(t.website),a.join(" · ")}function c(t){if(!t)return"";const a=t.logo?`<img src="${e(t.logo)}" alt="Logo institusi" class="standard-kop-logo" />`:"",o=t.foundation_name?`<p class="standard-kop-foundation">${e(t.foundation_name)}</p>`:"",n=e(i(t)||"-"),r=e(l(t)),s=e(t.name||"Institusi");return`<header class="standard-kop">
  <table class="standard-kop-inner">
    <tr>
      <td class="standard-kop-logo-cell">${a}</td>
      <td class="standard-kop-text">
        ${o}
        <p class="standard-kop-school">${s}</p>
        <p class="standard-kop-address">${n}</p>
        <p class="standard-kop-info">${r}</p>
      </td>
      <td class="standard-kop-spacer">&nbsp;</td>
    </tr>
  </table>
</header>
<style>
.standard-kop { width: 100%; border-bottom: 3px double #111; padding: 0 0 6px; margin-bottom: 10px; }
.standard-kop-inner { width: 100%; border-collapse: collapse; table-layout: auto; }
.standard-kop-inner td { border: none !important; padding: 0 !important; vertical-align: top; }
.standard-kop-logo-cell { width: 68px; text-align: left; padding-right: 8px !important; }
.standard-kop-spacer { width: 68px; border: none !important; }
.standard-kop-logo { width: 60px; height: 60px; object-fit: contain; }
.standard-kop-text { text-align: center; }
.standard-kop-foundation { margin: 0; font-family: "Times New Roman", serif; font-size: 12px; font-weight: 600; line-height: 1.2; text-transform: uppercase; letter-spacing: 0.02em; }
.standard-kop-school { margin: 0; font-family: "Times New Roman", serif; font-size: 15px; font-weight: 700; line-height: 1.18; text-transform: uppercase; }
.standard-kop-address { margin: 3px 0 0; font-family: Arial, Helvetica, sans-serif; font-size: 9px; line-height: 1.4; }
.standard-kop-info { margin: 2px 0 0; font-family: Arial, Helvetica, sans-serif; font-size: 8px; line-height: 1.35; }
</style>`}function f(t){return t?t.isi_html?!1:!!t.is_default:!0}export{c as r,f as s};
