/**
 * Utility functions untuk validasi form
 */

export const validators = {
  required: (value, message = 'Field ini wajib diisi') => {
    if (!value || (typeof value === 'string' && value.trim() === '')) {
      return message
    }
    return null
  },

  email: (value, message = 'Format email tidak valid') => {
    if (!value) return null // Skip jika kosong (gunakan required untuk mandatory)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(value)) {
      return message
    }
    return null
  },

  minLength: (value, min, message = null) => {
    if (!value) return null
    const msg = message || `Minimal ${min} karakter`
    if (value.length < min) {
      return msg
    }
    return null
  },

  maxLength: (value, max, message = null) => {
    if (!value) return null
    const msg = message || `Maksimal ${max} karakter`
    if (value.length > max) {
      return msg
    }
    return null
  },

  npsn: (value, message = 'NPSN harus terdiri dari 8 digit angka') => {
    if (!value) return null
    const npsnRegex = /^[0-9]{8}$/
    if (!npsnRegex.test(value)) {
      return message
    }
    return null
  },

  phone: (value, message = 'Format nomor telepon tidak valid') => {
    if (!value) return null
    const phoneRegex = /^[0-9]{10,15}$/
    if (!phoneRegex.test(value.replace(/[^0-9]/g, ''))) {
      return message
    }
    return null
  },

  url: (value, message = 'Format URL tidak valid') => {
    if (!value) return null
    try {
      new URL(value)
      return null
    } catch {
      return message
    }
  },

  match: (value, otherValue, message = 'Nilai tidak cocok') => {
    if (value !== otherValue) {
      return message
    }
    return null
  },

  date: (value, message = 'Format tanggal tidak valid') => {
    if (!value) return null
    const date = new Date(value)
    if (isNaN(date.getTime())) {
      return message
    }
    return null
  },

  datePast: (value, message = 'Tanggal harus di masa lalu') => {
    if (!value) return null
    const date = new Date(value)
    const now = new Date()
    if (date >= now) {
      return message
    }
    return null
  },

  numeric: (value, message = 'Harus berupa angka') => {
    if (!value) return null
    if (isNaN(value) || value.toString().trim() === '') {
      return message
    }
    return null
  }
}

/**
 * Validate form object dengan rules
 * @param {Object} form - Form data object
 * @param {Object} rules - Validation rules { field: [validators] }
 * @returns {Object} { isValid: boolean, errors: { field: errorMessage } }
 */
export function validateForm(form, rules) {
  const errors = {}
  
  for (const [field, fieldRules] of Object.entries(rules)) {
    const value = form[field]
    
    for (const rule of fieldRules) {
      let error = null
      
      if (typeof rule === 'function') {
        error = rule(value)
      } else if (typeof rule === 'object' && rule.validator) {
        error = rule.validator(value, rule.message)
      }
      
      if (error) {
        errors[field] = error
        break // Stop at first error
      }
    }
  }
  
  return {
    isValid: Object.keys(errors).length === 0,
    errors
  }
}

/**
 * Clear validation errors
 */
export function clearErrors(errors) {
  return Object.keys(errors).reduce((acc, key) => {
    acc[key] = ''
    return acc
  }, {})
}
