const procurement = {
    path: `/mmis/procurements`,
    name: `procurements`,
    // redirect: {
    //   name: 'purchase-request'
    // }
    component: () =>
        import ("../pages/procurements/purchase_request/index.vue")
};
const purchase = {
    path: `/mmis/procurements/purchase-request`,
    name: `purchase-request`,
    component: () =>
        import ("../pages/procurements/purchase_request/index.vue")
};

const quotation = {
    path: `/mmis/procurements/request-quotation`,
    name: `request-quotation`,
    component: () =>
        import ("../pages/procurements/quotation_request/index.vue")
};

const canvas = {
    path: `/mmis/procurements/canvas`,
    name: `canvas`,
    component: () =>
        import ("../pages/procurements/canvas/index.vue")
};

const order = {
    path: `/mmis/procurements/purchase-order`,
    name: `purchase-order`,
    component: () =>
        import ("../pages/procurements/purchase_order/index.vue")
};

const component = [purchase, procurement, quotation, canvas, order];
export default component;