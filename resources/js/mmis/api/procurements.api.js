import { httpApiClient } from "@global/axios";
import { store } from "@global/store"
const purchase = "purchase-request";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token

export const apiGetAllPurchaseRequest = (query)  => httpApiClient.get(`${purchase}?`+query);
export const apiCreatePurchaseRequest = (payload)  => httpApiClient.post(`${purchase}`, payload, { headers: {
  "Content-Type": "multipart/form-data",
},});
// export const apiGetUser = (id)  => Axios.get(`${resource}/`+id);
// export const apiCreateUser = (payload)  => Axios.post(`${resource}/create`, payload);
// export const apiUpdateUser = (id, payload)  => Axios.put(`${resource}/`+id, payload);
// export const apiToggleVerifiedStatus = (id)  => Axios.put(`${resource}/toggle-verfied/${id}`);

// export const apiDeleteUser = (id)  => Axios.delete(`${resource}/`+id);