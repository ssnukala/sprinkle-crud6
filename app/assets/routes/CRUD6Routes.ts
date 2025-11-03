export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            title: '',        // Empty string allows breadcrumb initialization; components update dynamically
            description: ''   // Empty string allows breadcrumb initialization; components update dynamically
        },
        children: [
            {
                path: '',
                name: 'crud6.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    },
                    title: '',        // Empty string allows breadcrumb initialization; PageList.vue updates dynamically
                    description: ''   // Empty string allows breadcrumb initialization; PageList.vue updates dynamically
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    title: '',        // Empty string allows breadcrumb initialization; PageRow/PageMasterDetail update dynamically
                    description: 'CRUD6.INFO_PAGE',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageDynamic.vue')
            }
        ]
    }
]
