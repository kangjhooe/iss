/**
 * Composable untuk form validation yang reusable
 * 
 * Usage:
 * import { useFormValidation } from '@/composables/useFormValidation'
 * 
 * const { form, fieldErrors, validateField, validateForm, resetForm } = useFormValidation({
 *   initialValues: { name: '', email: '' },
 *   rules: {
 *     name: [(v) => validators.required(v, 'Nama wajib diisi')],
 *     email: [
 *       (v) => validators.required(v, 'Email wajib diisi'),
 *       (v) => validators.email(v, 'Format email tidak valid')
 *     ]
 *   }
 * })
 */

import { ref, reactive } from 'vue'
import { validateForm as validateFormUtil, validators } from '@/utils/validation'

export function useFormValidation(config = {}) {
  const { initialValues = {}, rules = {} } = config

  // Create reactive form from initial values
  const form = reactive({ ...initialValues })

  // Create reactive field errors
  const fieldErrors = reactive(
    Object.keys(initialValues).reduce((acc, key) => {
      acc[key] = ''
      return acc
    }, {})
  )

  /**
   * Validate single field
   */
  const validateField = (fieldName) => {
    if (!rules[fieldName]) {
      return true
    }

    const validation = validateFormUtil(
      { [fieldName]: form[fieldName] },
      { [fieldName]: rules[fieldName] }
    )

    fieldErrors[fieldName] = validation.errors[fieldName] || ''
    return !validation.errors[fieldName]
  }

  /**
   * Validate all fields
   */
  const validateAll = () => {
    const validation = validateFormUtil(form, rules)
    
    // Update all field errors
    Object.keys(fieldErrors).forEach(key => {
      fieldErrors[key] = validation.errors[key] || ''
    })

    return validation.isValid
  }

  /**
   * Clear all errors
   */
  const clearErrors = () => {
    Object.keys(fieldErrors).forEach(key => {
      fieldErrors[key] = ''
    })
  }

  /**
   * Reset form to initial values
   */
  const resetForm = () => {
    Object.keys(initialValues).forEach(key => {
      form[key] = initialValues[key]
    })
    clearErrors()
  }

  /**
   * Check if form is valid
   */
  const isValid = () => {
    return validateAll()
  }

  /**
   * Get all errors as object
   */
  const getErrors = () => {
    return { ...fieldErrors }
  }

  /**
   * Check if form has errors
   */
  const hasErrors = () => {
    return Object.values(fieldErrors).some(error => error !== '')
  }

  return {
    form,
    fieldErrors,
    validateField,
    validateAll,
    clearErrors,
    resetForm,
    isValid,
    getErrors,
    hasErrors,
    validators // Export validators for convenience
  }
}
