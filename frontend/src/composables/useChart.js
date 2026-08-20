export const CHART_PALETTE = [
  '#059669',
  '#0d9488',
  '#0284c7',
  '#f59e0b',
  '#ef4444',
  '#7c3aed',
  '#64748b',
  '#14b8a6',
  '#f97316',
  '#6366f1',
]

export const ATTENDANCE_COLORS = {
  hadir: '#059669',
  izin: '#0284c7',
  sakit: '#f59e0b',
  alpha: '#ef4444',
  dinas_luar: '#7c3aed',
  cuti: '#8b5cf6',
  wfh: '#0ea5e9',
}

export const ATTENDANCE_LABELS = {
  hadir: 'Hadir',
  izin: 'Izin',
  sakit: 'Sakit',
  alpha: 'Alpha',
  dinas_luar: 'Dinas luar',
  cuti: 'Cuti',
  wfh: 'WFH',
}

export const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { maxRotation: 45, minRotation: 0, font: { size: 11 } }, grid: { display: false } },
    y: { beginAtZero: true, ticks: { precision: 0 } },
  },
}

export const chartOptionsBarGrouped = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
  scales: {
    x: { ticks: { font: { size: 11 } }, grid: { display: false } },
    y: { beginAtZero: true, ticks: { precision: 0 } },
  },
}

export const chartOptionsBarStacked = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
  scales: {
    x: { stacked: true, ticks: { font: { size: 11 } }, grid: { display: false } },
    y: { stacked: true, beginAtZero: true },
  },
}

export const chartOptionsDoughnut = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
}

export const chartOptionsLine = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { ticks: { font: { size: 11 } }, grid: { display: false } },
    y: { beginAtZero: true },
  },
}

export function doughnutFromEntries(entries) {
  const filtered = (entries || []).filter((e) => Number(e.value) > 0)
  if (!filtered.length) return null
  return {
    labels: filtered.map((e) => e.label),
    datasets: [
      {
        data: filtered.map((e) => Number(e.value)),
        backgroundColor: filtered.map((e, i) => e.color || CHART_PALETTE[i % CHART_PALETTE.length]),
        borderWidth: 0,
      },
    ],
  }
}

export function doughnutFromCounts(counts, labels = ATTENDANCE_LABELS, colors = ATTENDANCE_COLORS) {
  if (!counts) return null
  return doughnutFromEntries(
    Object.keys(labels).map((key) => ({
      label: labels[key],
      value: Number(counts[key] || 0),
      color: colors[key],
    }))
  )
}

export function barFromSeries(labels, values, datasetLabel = 'Jumlah', color = '#059669') {
  if (!labels?.length) return null
  return {
    labels,
    datasets: [
      {
        label: datasetLabel,
        data: values,
        backgroundColor: color,
        borderRadius: 6,
        maxBarThickness: 36,
      },
    ],
  }
}

export function lineFromSeries(labels, values, datasetLabel = 'Jumlah', color = '#059669') {
  if (!labels?.length) return null
  return {
    labels,
    datasets: [
      {
        label: datasetLabel,
        data: values,
        borderColor: color,
        backgroundColor: 'rgba(5, 150, 105, 0.12)',
        fill: true,
        tension: 0.3,
        pointRadius: 3,
      },
    ],
  }
}

export function countStatuses(rows, statusKey = 'status') {
  const counts = { hadir: 0, izin: 0, sakit: 0, alpha: 0, dinas_luar: 0, cuti: 0, wfh: 0 }
  for (const row of rows || []) {
    const key = String(row?.[statusKey] || '').toLowerCase().replace(/\s+/g, '_')
    if (key in counts) counts[key]++
  }
  return counts
}
