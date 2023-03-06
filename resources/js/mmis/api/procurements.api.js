import { httpApiClient } from "@global/axios";
import { store } from "@global/store"
const purchase = "purchase-request";
const canvas = "canvas";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token

export const apiGetAllPurchaseRequest = (query) => httpApiClient.get(`${purchase}?` + query);
export const apiGetPurchaseRequest = (id) => httpApiClient.get(`${purchase}/` + id);
export const apiRemovePurchaseRequest = (id) => httpApiClient.delete(`${purchase}/` + id);
export const apiApprovePurchaseRequestItems = (payload) => httpApiClient.post(`${purchase}-items/`, payload);
export const apiCreatePurchaseRequest = (payload) => httpApiClient.post(`${purchase}`, payload, {
  headers: {
    "Content-Type": "multipart/form-data",
  },
});
export const apiUpdatePurchaseRequest = (id, payload) => httpApiClient.post(`${purchase}/` + id, payload, {
  headers: {
    "Content-Type": "multipart/form-data",
  },
});
export const apiUpdatePurchaseRequestItemAttachment = (id, payload) => httpApiClient.post(`update-item-attachment/` + id, payload, {
  headers: {
    "Content-Type": "multipart/form-data",
  },
});

export const apiRemovePurchaseRequestItem = (id) => httpApiClient.delete(`remove-item/` + id);

// canvas
export const apiAddCanvas = (payload) => httpApiClient.post(`${canvas}`, payload, {
  headers: {
    "Content-Type": "multipart/form-data",
  },
});
export const apiGetAllCanvas = (query) => httpApiClient.get(`${canvas}?` + query);
export const apiRemoveCanvas = (id) => httpApiClient.delete(`${canvas}/${id}`);
export const apiUpdateIsRecommended = (id, payload) => httpApiClient.put(`update-isrecommended/${id}`, payload);
export const apiSubmitCanvas = (payload) => httpApiClient.post(`submit-${canvas}`, payload);