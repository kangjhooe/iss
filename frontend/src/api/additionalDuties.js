import api from './index'

export const additionalDutiesApi = {
  getAll() {
    return api.get('/v1/additional-duties')
  }
}
