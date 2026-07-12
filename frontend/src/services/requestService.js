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
    const formData = new FormData()
    Object.entries(data).forEach(([key, value]) => {
        if (key !== 'images') formData.append(key, value)
    })
    ;(data.images || []).forEach((image) => formData.append('images[]', image))
    return api.post('/requests', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
}

export const getRequestImage = (id) => {
    return api.get(`/request-images/${id}`, { responseType: 'blob' })
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

export const getAssignedRequestById = (id) => {
    return api.get(`/technician/requests/${id}`)
}

export const updateAssignedRequest = (id, data) => {
    return api.put(`/technician/requests/${id}`, data)
}
