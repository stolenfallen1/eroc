import Axios from "@global/axios";
const purchase = "/purchase-request";

export const apiGetAllPurchaseRequest = (query)  => Axios.get(`${resource}?`+query);
// export const apiGetUser = (id)  => Axios.get(`${resource}/`+id);
// export const apiCreateUser = (payload)  => Axios.post(`${resource}/create`, payload);
// export const apiUpdateUser = (id, payload)  => Axios.put(`${resource}/`+id, payload);
// export const apiToggleVerifiedStatus = (id)  => Axios.put(`${resource}/toggle-verfied/${id}`);

// export const apiDeleteUser = (id)  => Axios.delete(`${resource}/`+id);