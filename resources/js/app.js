import './bootstrap';

import { createApp } from 'vue'

// Import components for frontend
import Home from './components/frontend/pages/home/Home.vue'
import Contact from './components/frontend/pages/contact/Contact.vue'
import CommonPage from './components/frontend/pages/types/CommonPage.vue'
import BlogNewsPage from './components/frontend/pages/types/BlogNewsPage.vue'
import GalleryPage from './components/frontend/pages/types/GalleryPage.vue'
// import Errors from './components/frontend/pages/contact/Error.vue'
// Import components for backend
import Dashboard from './components/backend/pages/Dashboard.vue'
import SocialLinkage from './components/backend/pages/SocialLinkage.vue'

// Create a new Vue instance for frontend
createApp(Home).mount("#home")
createApp(Contact).mount("#contact")
createApp(CommonPage).mount("#common-page")
createApp(BlogNewsPage).mount("#blog-news-page")
createApp(GalleryPage).mount("#gallery-page")
// createApp(Errors).mount("#errors")
// Create a new Vue instance for backend
createApp(Dashboard).mount("#dashboard")
createApp(SocialLinkage).mount("#social-linkage")
