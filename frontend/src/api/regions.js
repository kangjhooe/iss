import api from './index'

export const regionsApi = {
  provinces() {
    return api.get('/v1/public/regions/provinces')
  },
  regencies(provinceCode) {
    return api.get('/v1/public/regions/regencies', { params: { province_code: provinceCode } })
  },
  districts(regencyCode) {
    return api.get('/v1/public/regions/districts', { params: { regency_code: regencyCode } })
  },
  villages(districtCode) {
    return api.get('/v1/public/regions/villages', { params: { district_code: districtCode } })
  },
}
