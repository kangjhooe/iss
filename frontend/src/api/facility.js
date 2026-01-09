import api from './index'

export const facilityApi = {
  // Land (Tanah)
  getLands(params) {
    return api.get('/v1/facility/lands', { params })
  },
  createLand(data) {
    return api.post('/v1/facility/lands', data)
  },
  updateLand(id, data) {
    return api.put(`/v1/facility/lands/${id}`, data)
  },
  deleteLand(id) {
    return api.delete(`/v1/facility/lands/${id}`)
  },

  // Building (Gedung)
  getBuildings(params) {
    return api.get('/v1/facility/buildings', { params })
  },
  createBuilding(data) {
    return api.post('/v1/facility/buildings', data)
  },
  updateBuilding(id, data) {
    return api.put(`/v1/facility/buildings/${id}`, data)
  },
  deleteBuilding(id) {
    return api.delete(`/v1/facility/buildings/${id}`)
  },

  // Room (Ruangan)
  getRooms(params) {
    return api.get('/v1/facility/rooms', { params })
  },
  createRoom(data) {
    return api.post('/v1/facility/rooms', data)
  },
  updateRoom(id, data) {
    return api.put(`/v1/facility/rooms/${id}`, data)
  },
  deleteRoom(id) {
    return api.delete(`/v1/facility/rooms/${id}`)
  }
}
