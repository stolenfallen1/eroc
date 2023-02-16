import {httpApiClient} from "@global/axios";
import { store } from "../store"
const categories = "categories";
const subCategories = "sub-categories";
const classifications = "classifications";
httpApiClient.defaults.headers.common['Authorization'] = 'Bearer ' + store.getters.user.api_token
export const apiGetAllCategories = (query)  => httpApiClient.get(`${categories}?`+query);
export const apiGetAllSubCategories = (query)  => httpApiClient.get(`${subCategories}?`+query);
export const apiGetAllClassifications = (query)  => httpApiClient.get(`${classifications}?`+query);