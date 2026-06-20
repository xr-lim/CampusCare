import axios from 'axios'

const API = 'http://localhost/CampusCare/backend/public'

export const register = (data)=>
{
  return axios.post(
    `${API}/register`,
    data
  )
}

export const login = (data)=>
{
  return axios.post(
    `${API}/login`,
    data
  )
}

export const profile = () =>
{
  return axios.get(
    `${API}/profile`,
    {
      headers:{
        Authorization:
        `Bearer ${localStorage.getItem('token')}`
      }
    }
  )
}