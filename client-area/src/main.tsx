import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import { isWebtraderHost } from './lib/webtrader-host.ts'

const root = createRoot(document.getElementById('root')!)

// Dynamic import (not a static one) so the OTHER app's code is never even
// fetched -- a webtrader host never downloads App.tsx's JS at all, and a
// client-area host never downloads WebtraderApp.tsx's. This is what makes
// the isolation between the two real at the network level, not just at the
// "which component renders" level.
const loadRoot = isWebtraderHost()
  ? import('./webtrader/WebtraderApp.tsx')
  : import('./App.tsx')

loadRoot.then(({ default: Root }) => {
  root.render(
    <StrictMode>
      <Root />
    </StrictMode>,
  )
})
