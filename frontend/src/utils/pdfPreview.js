import { parseBlobError } from '@/utils/blobError'

function toPdfBlob(source) {
  if (source instanceof Blob) {
    return source.type && source.type !== 'application/pdf'
      ? new Blob([source], { type: 'application/pdf' })
      : source
  }
  const data = source?.data ?? source
  if (data instanceof Blob) {
    return data.type && data.type !== 'application/pdf'
      ? new Blob([data], { type: 'application/pdf' })
      : data
  }
  return new Blob([data], { type: 'application/pdf' })
}

async function assertPdfMagic(blob, fallback) {
  const head = await blob.slice(0, 5).text()
  if (head.startsWith('%PDF')) {
    return blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' })
  }

  try {
    const json = JSON.parse(await blob.text())
    throw new Error(json?.message || fallback)
  } catch (e) {
    if (e instanceof Error && e.message && e.message !== fallback) {
      if (!(e instanceof SyntaxError) && !/JSON/i.test(e.message)) {
        throw e
      }
    }
    throw new Error(fallback)
  }
}

/**
 * Pastikan respons axios (responseType: blob) benar-benar PDF.
 * Menolak JSON error dan HTML SPA yang sering lolos sebagai HTTP 200.
 *
 * @param {import('axios').AxiosResponse|Blob} res
 * @param {string} fallback
 * @returns {Promise<Blob>}
 */
export async function ensurePdfBlob(res, fallback = 'Gagal membuka PDF.') {
  // Sudah Blob mentah (hasil pemanggilan sebelumnya)
  if (res instanceof Blob) {
    return assertPdfMagic(toPdfBlob(res), fallback)
  }

  const status = res?.status ?? 0
  const ct = String(res?.headers?.['content-type'] || '')
  const data = res?.data

  if (status && status !== 200) {
    throw new Error(await parseBlobError({ response: res }, fallback))
  }

  if (ct.includes('application/json') || ct.includes('text/html')) {
    throw new Error(await parseBlobError({ response: res }, fallback))
  }

  return assertPdfMagic(toPdfBlob(data ?? res), fallback)
}

/**
 * Buka PDF di tab preview baru (bukan unduh langsung).
 * Menerima Blob atau respons axios; menolak HTML/JSON yang menyamar sebagai PDF.
 *
 * @param {Blob|{ data: Blob|ArrayBuffer }|import('axios').AxiosResponse} source
 * @param {string} filename — judul tab / nama file
 * @returns {Promise<boolean>} false jika popup diblokir
 */
export async function openPdfBlob(source, filename = 'document.pdf') {
  const blob = await ensurePdfBlob(source, 'File PDF tidak valid atau gagal diunduh.')

  const url = window.URL.createObjectURL(blob)
  const opened = window.open(url, '_blank', 'noopener,noreferrer')
  if (opened) {
    try {
      opened.document.title = filename
    } catch {
      // cross-origin / blocked — abaikan
    }
  }
  setTimeout(() => window.URL.revokeObjectURL(url), 60000)
  return !!opened
}

/**
 * Respons axios → validasi PDF → buka preview ber-toolbar.
 *
 * @param {import('axios').AxiosResponse} res
 * @param {string} title
 * @param {string} fallback
 * @returns {Promise<boolean>}
 */
export async function openPdfFromAxios(res, title = 'Preview PDF', fallback = 'Gagal membuka PDF.') {
  const blob = await ensurePdfBlob(res, fallback)
  return openPdfPreviewWindow(blob, title)
}

/**
 * Preview PDF dengan toolbar cetak sederhana.
 * @param {Blob} blob
 * @param {string} title
 * @returns {boolean}
 */
export function openPdfPreviewWindow(blob, title = 'Preview PDF') {
  const pdfBlob = blob instanceof Blob
    ? (blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' }))
    : new Blob([blob], { type: 'application/pdf' })

  const url = URL.createObjectURL(pdfBlob)
  const win = window.open('', '_blank')
  if (!win) {
    URL.revokeObjectURL(url)
    return false
  }

  const safeTitle = String(title).replace(/[<>&"]/g, '')
  win.document.write(`<!DOCTYPE html><html><head><title>${safeTitle}</title>
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
      <h1>${safeTitle}<span class="hint">Preview — cetak dari tombol di bawah atau dari viewer PDF</span></h1>
      <div class="actions">
        <button class="btn-print" type="button" onclick="document.getElementById('pdfFrame').contentWindow.focus(); document.getElementById('pdfFrame').contentWindow.print();">Cetak</button>
        <button class="btn-close" type="button" onclick="window.close()">Tutup</button>
      </div>
    </div>
    <iframe id="pdfFrame" src="${url}" title="Preview PDF"></iframe>
  </body></html>`)
  win.document.close()
  setTimeout(() => URL.revokeObjectURL(url), 120_000)
  return true
}
