export default [
    {
        path: '/crud6/:model',
        meta: {
            auth: {},
            // Remove title from parent - it should be on child routes only
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
                    // NO title - PageList.vue sets it dynamically
                },
                component: () => import('../views/PageList.vue')
            },
            {
                path: ':id',
                name: 'crud6.view',
                meta: {
                    title: 'CRUD6.PAGE',  // Title on view route for breadcrumb placeholder
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
