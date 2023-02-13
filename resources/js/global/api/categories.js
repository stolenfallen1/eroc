import Axios from "@global/axios";
const categories = "api/categories";
const subCategories = "api/sub-categories";
const classifications = "api/classifications";

export const apiGetAllCategories = (query)  => Axios.get(`${categories}?`+query);
export const apiGetAllSubCategories = (query)  => Axios.get(`${subCategories}?`+query);
export const apiGetAllClassifications = (query)  => Axios.get(`${classifications}?`+query);