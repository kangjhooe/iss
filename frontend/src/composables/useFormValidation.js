/**
 * Composable untuk form validation yang reusable
 * 
 * Usage:
 * import { useFormValidation } from '@/composables/useFormValidation'
 * 
 * const { form, fieldErrors, validateField, validateAll, resetForm } = useFormValidation({
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

import { ref, reactive, isRef } from 'vue'
import { validateForm as validateFormUtil, validators } from '@/utils/validation'

export function useFormValidation(config = {}) {
  const { initialValues = {}, rules = {}, form: providedForm = null } = config

  const form = providedForm || reactive({ ...initialValues })
  const getFormData = () => (isRef(form) ? form.value : form)
  const initialKeys = Object.keys(initialValues).length ? initialValues : getFormData()

  // Create reactive field errors
  const fieldErrors = reactive(
    Object.keys(initialKeys).reduce((acc, key) => {
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
      { [fieldName]: getFormData()[fieldName] },
      { [fieldName]: rules[fieldName] }
    )

    fieldErrors[fieldName] = validation.errors[fieldName] || ''
    return !validation.errors[fieldName]
  }

  /**
   * Validate all fields
   */
  const validateAll = () => {
    const validation = validateFormUtil(getFormData(), rules)
    
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
      if (isRef(form)) {
        form.value[key] = initialValues[key]
      } else {
        form[key] = initialValues[key]
      }
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

  /**
   * Set errors from server response or custom errors
   */
  const setErrors = (errors = {}) => {
    Object.keys(fieldErrors).forEach(key => {
      fieldErrors[key] = ''
    })

    Object.entries(errors).forEach(([key, value]) => {
      if (Object.prototype.hasOwnProperty.call(fieldErrors, key)) {
        fieldErrors[key] = Array.isArray(value) ? value[0] : value
      }
    })
  }

  return {
    form,
    fieldErrors,
    validateField,
    validateAll,
    validateForm: validateAll, // Alias for backward compatibility
    clearErrors,
    resetForm,
    isValid,
    getErrors,
    hasErrors,
    setErrors,
    validators // Export validators for convenience
  }
}
