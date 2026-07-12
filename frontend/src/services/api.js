import axios from 'axios'

const api = axios.create({
    baseURL: 'http://localhost/CampusCare/backend/public',

    headers: {
        'Content-Type': 'application/json'
    }
})

api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')

        if (token) {
            config.headers.Authorization = 
                `Bearer ${token}`
        }

        return config
    },

    (error) => {
        return Promise.reject(error)
    }
)

api.interceptors.response.use(
    response => response,

    error => {

        const status = error.response?.status
        const url = error.config?.url
        const message = error.response?.data?.message || ''

        if (status === 401 && url === '/login') {
            return Promise.reject(error)
        }

        if (status === 401) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')

            window.location.href =
                `/error?code=401&message=${encodeURIComponent(message)}`
        }

        if (status === 403) {
            window.location.href =
                `/error?code=403&message=${encodeURIComponent(message)}`
        }

        return Promise.reject(error)
    }
)

export default api

// ========== Categories ==========
export const getCategories = async () => {
    return api.get('/categories')
}

export const getCategory = async (id) => {
    return api.get(`/categories/${id}`)
}

export const createCategory = async (data, token) => {
    return api.post('/categories', data, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}

export const updateCategory = async (id, data, token) => {
    return api.put(`/categories/${id}`, data, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}

export const deleteCategory = async (id, token) => {
    return api.delete(`/categories/${id}`, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}

// ========== Locations ==========
export const getLocations = async () => {
    return api.get('/locations')
}

export const getLocation = async (id) => {
    return api.get(`/locations/${id}`)
}

export const createLocation = async (data, token) => {
    return api.post('/locations', data, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}

export const updateLocation = async (id, data, token) => {
    return api.put(`/locations/${id}`, data, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}

export const deleteLocation = async (id, token) => {
    return api.delete(`/locations/${id}`, {
        headers: {
            Authorization: `Bearer ${token}`
        }
    })
}