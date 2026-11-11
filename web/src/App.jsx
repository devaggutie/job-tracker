import { Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './AuthContext';
import Protected from './components/Protected';
import Shell from './components/Shell';
import LoginPage from './pages/LoginPage';
import ApplicationsPage from './pages/ApplicationsPage';
import BoardPage from './pages/BoardPage';
import ApplicationFormPage from './pages/ApplicationFormPage';
import ApplicationDetailPage from './pages/ApplicationDetailPage';

export default function App() {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route element={<Protected />}>
          <Route element={<Shell />}>
            <Route path="/" element={<ApplicationsPage />} />
            <Route path="/board" element={<BoardPage />} />
            <Route path="/applications/new" element={<ApplicationFormPage />} />
            <Route path="/applications/:id" element={<ApplicationDetailPage />} />
            <Route path="/applications/:id/edit" element={<ApplicationFormPage />} />
          </Route>
        </Route>
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </AuthProvider>
  );
}
