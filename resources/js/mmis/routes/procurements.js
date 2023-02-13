const procurement = {
  path: `/mmis/procurements`,
  name: `procurements`,
  // redirect: {
  //   name: 'purchase-request'
  // }
  component: () => import("../pages/procurements/dashboard/index.vue")
};
const purchase = {
  path: `/mmis/procurements/purchase-request`,
  name: `purchase-request`,
  component: () => import("../pages/procurements/purchase_request/index.vue")
};

const quotation = {
  path: `/mmis/procurements/request-quotation`,
  name: `request-quotation`,
  component: () => import("../pages/procurements/quotation_request/index.vue")
};

const component = [purchase, procurement, quotation];
export default component;