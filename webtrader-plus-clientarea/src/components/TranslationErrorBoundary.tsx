import React, { Component, ReactNode } from 'react';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  errorMessage?: string;
}

class TranslationErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError(error: Error): State {
    // Check if the error is related to Google Translate DOM conflicts
    const isTranslationError = 
      error.message.includes('removeChild') ||
      error.message.includes('insertBefore') ||
      error.message.includes('The node to be removed is not a child') ||
      error.message.includes('Failed to execute \'removeChild\' on \'Node\'') ||
      error.message.includes('Failed to execute \'insertBefore\' on \'Node\'') ||
      error.stack?.includes('google.translate') ||
      error.stack?.includes('goog-te');

    if (isTranslationError) {
      console.warn('Translation-related error caught by boundary:', error);
      return {
        hasError: true,
        errorMessage: 'Translation conflict detected - attempting recovery'
      };
    }

    // Re-throw non-translation errors
    throw error;
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    // Log the error for debugging
    console.warn('TranslationErrorBoundary caught an error:', error, errorInfo);
    
    // Auto-recover after a short delay
    setTimeout(() => {
      this.setState({ hasError: false });
    }, 1000);
  }

  render() {
    if (this.state.hasError) {
      return this.props.fallback || (
        <div style={{ padding: '20px', textAlign: 'center' }}>
          <p>Recovering from translation conflict...</p>
        </div>
      );
    }

    return this.props.children;
  }
}

export default TranslationErrorBoundary;