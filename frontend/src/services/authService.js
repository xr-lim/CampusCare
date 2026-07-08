import api from './api'

export const register = (data) => {
    return api.post(
        '/register',
        data
    )
}

export const login = (data) => {
    return api.post(
        '/login',
        data
    )
}

export const profile = () => {
    return api.get(
        '/profile'
    )
}

export const updateProfile = (data) => {
    return api.put(
        '/profile',
        data
    )
}

export const deleteProfile = () => {
    return api.delete(
        '/profile'
    )
}