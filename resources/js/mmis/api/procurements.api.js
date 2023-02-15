import Axios from "@global/axios";
const purchase = "api/purchase-request";

export const apiGetAllPurchaseRequest = (query)  => Axios.get(`${purchase}?`+query);
export const apiCreatePurchaseRequest = (payload)  => Axios.post(`${purchase}`, payload, { headers: {
  "Content-Type": "multipart/form-data",
},});
// export const apiGetUser = (id)  => Axios.get(`${resource}/`+id);
// export const apiCreateUser = (payload)  => Axios.post(`${resource}/create`, payload);
// export const apiUpdateUser = (id, payload)  => Axios.put(`${resource}/`+id, payload);
// export const apiToggleVerifiedStatus = (id)  => Axios.put(`${resource}/toggle-verfied/${id}`);

// export const apiDeleteUser = (id)  => Axios.delete(`${resource}/`+id);