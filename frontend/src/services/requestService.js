import api from './api'

export const getCategories = () => {
    return api.get('/categories')
}

export const getLocations = () => {
    return api.get('/locations')
}

export const getMyRequests = () => {
    return api.get('/requests/my')
}

export const getRequestById = (id) => {
    return api.get(`/requests/${id}`)
}

export const createRequest = (data) => {
    return api.post('/requests', data)
}

export const updateRequest = (id, data) => {
    return api.put(`/requests/${id}`, data)
}

export const cancelRequest = (id) => {
    return api.delete(`/requests/${id}`)
}

export const getAssignedRequests = () => {
    return api.get('/technician/requests')
}

export const updateAssignedRequest = (id, data) => {
    return api.put(`/technician/requests/${id}`, data)
}
