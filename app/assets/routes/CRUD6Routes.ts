export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            title: 'CRUD6.PAGE',
            description: 'CRUD6.PAGE_DESCRIPTION'
        },
        children: [
            {
                path: '',
                name: 'crud6.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    // Note: title is NOT set here to avoid duplicate breadcrumbs
                    // The record name is added dynamically by PageRow.vue via useCRUD6Breadcrumbs
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
