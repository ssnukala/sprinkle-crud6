/**
 * Test routes for master-detail examples
 * 
 * These routes provide working examples of the master-detail functionality:
 * - testc6/orders - Order entry with line items (one-to-many)
 * - testc6/products - Product category assignment (many-to-many)
 */
export default [
    {
        path: '/testc6/orders',
        meta: {
            auth: {},
            title: 'Test Orders',
            description: 'Order Entry Test - Master-Detail Example'
        },
        children: [
            {
                path: '',
                name: 'testc6.orders.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageList.vue'),
                props: () => ({ model: 'orders' })
            },
            {
                path: 'create',
                name: 'testc6.orders.create',
                meta: {
                    title: 'Create Order',
                    description: 'Create new order with line items',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/TestOrderEntry.vue')
            },
            {
                path: ':id',
                name: 'testc6.orders.edit',
                meta: {
                    title: 'Edit Order',
                    description: 'Edit order with line items',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/TestOrderEntry.vue')
            }
        ]
    },
    {
        path: '/testc6/products',
        meta: {
            auth: {},
            title: 'Test Products',
            description: 'Product Category Assignment Test - Many-to-Many Example'
        },
        children: [
            {
                path: '',
                name: 'testc6.products.list',
                meta: {
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/PageList.vue'),
                props: () => ({ model: 'products' })
            },
            {
                path: ':id/categories',
                name: 'testc6.products.categories',
                meta: {
                    title: 'Manage Product Categories',
                    description: 'Assign categories to product',
                    permission: {
                        slug: 'uri_crud6'
                    }
                },
                component: () => import('../views/TestProductCategory.vue')
            }
        ]
    }
]
