import { getNssLabel } from '@/utils/institution'

function escapeHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

export function buildInstitutionAddress(inst) {
  if (!inst) return ''
  const built = [
    inst.address,
    inst.village ? `Desa/Kel. ${inst.village}` : null,
    inst.sub_district ? `Kec. ${inst.sub_district}` : null,
    inst.district,
    inst.province,
    inst.postal_code
  ].filter(Boolean).join(', ')
  if (built) return built
  return inst.full_address || ''
}

export function buildInstitutionInfoLine(inst) {
  if (!inst) return 'NPSN: -'
  const parts = [`NPSN: ${inst.npsn || '-'}`]
  if (inst.nss) parts.push(`${getNssLabel(inst.level)}: ${inst.nss}`)
  if (inst.phone) parts.push(`Telp: ${inst.phone}`)
  if (inst.email) parts.push(`Email: ${inst.email}`)
  if (inst.website) parts.push(inst.website)
  return parts.join(' · ')
}

/**
 * Kop standar laporan (KOP_STANDAR.md) — sama dengan Alumni, Report, dll.
 * @param {object|null} inst - active_institution dari auth
 */
export function renderStandardLetterheadHtml(inst) {
  if (!inst) return ''

  const logo = inst.logo
    ? `<img src="${escapeHtml(inst.logo)}" alt="Logo institusi" class="standard-kop-logo" />`
    : ''
  const foundation = inst.foundation_name
    ? `<p class="standard-kop-foundation">${escapeHtml(inst.foundation_name)}</p>`
    : ''
  const address = escapeHtml(buildInstitutionAddress(inst) || '-')
  const info = escapeHtml(buildInstitutionInfoLine(inst))
  const school = escapeHtml(inst.name || 'Institusi')

  return `<header class="standard-kop">
  <table class="standard-kop-inner">
    <tr>
      <td class="standard-kop-logo-cell">${logo}</td>
      <td class="standard-kop-text">
        ${foundation}
        <p class="standard-kop-school">${school}</p>
        <p class="standard-kop-address">${address}</p>
        <p class="standard-kop-info">${info}</p>
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
</style>`
}

/** @param {object|null} kop - kop surat record */
export function shouldUseInstitutionLetterhead(kop) {
  if (!kop) return true
  if (kop.isi_html) return false
  return !!kop.is_default
}
