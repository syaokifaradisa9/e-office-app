import { createContext, useContext } from 'react';

export const SidebarCollapseContext = createContext(false);

export const useSidebarCollapse = () => useContext(SidebarCollapseContext);
