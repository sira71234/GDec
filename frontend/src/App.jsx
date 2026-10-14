import { RouterProvider } from "react-router-dom";
import router from "./routes";
import { PanierProvider } from "./context/PanierContext";

function App() {
  return (
    <PanierProvider>
      <RouterProvider router={router} />
    </PanierProvider>
  );
}

export default App;
