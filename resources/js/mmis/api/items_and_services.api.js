import { httpApiClient } from "@global/axios";
import { store } from "@global/store"
const iteasandservices = "items-and-services";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token

export const apiGetAllItemsAndServices = (query) => httpApiClient.get(`${iteasandservices}?` + query);
export const apiCreateItemandServices = (payload) => httpApiClient.post(`${iteasandservices}`, payload);
export const apiUpdateItemandServices = (id, payload) => httpApiClient.post(`${iteasandservices}/` + id, payload);
export const apiRemoveItemsAndServices = (id) => httpApiClient.delete(`${iteasandservices}/` + id);