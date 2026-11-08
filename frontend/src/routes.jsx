import Home from "./pages/Home";
import Commandes from "./pages/Commandes";
import AdminLogin from "./pages/AdminLogin";
import AdminDashboard from "./pages/AdminDashboard";
import Layout from "./composants/Layout";
import { PageErreur, PageIntrouvable } from "./pages/PageErreur";

import { createBrowserRouter } from "react-router-dom";

const router = createBrowserRouter([
  {
    element: <Layout />,
    errorElement: <PageErreur />,
    children: [
      { path: "/", element: <Home /> },
      { path: "/commande", element: <Commandes /> },
      { path: "*", element: <PageIntrouvable /> },
    ],
  },
  { path: "/admin/login", element: <AdminLogin />, errorElement: <PageErreur /> },
  {
    path: "/admin/dashboard",
    element: <AdminDashboard />,
    errorElement: <PageErreur />,
  },
]);

export default router;
