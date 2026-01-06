import Editor, { OnChange } from '@monaco-editor/react';
import React, { ReactElement } from 'react';
import { xcodeDefault } from './themes';

interface QuietEditorProp {
  width?: string | number;
  height?: string | number;
  value?: string;
  language?: string;
  readOnly?: boolean;
  lineNumbers?: 'on' | 'off' | 'relative' | 'interval';
  folding?: boolean;
  renderLineHighlight?: 'all' | 'line' | 'none' | 'gutter';
  onChange?: OnChange;
}

const QuietEditor = (props: QuietEditorProp): ReactElement => {
  const {
    width,
    lineNumbers = 'on',
    height,
    value,
    folding = true,
    language,
    readOnly = false,
    renderLineHighlight = 'all',
    onChange,
  } = props;

  function editorWillMount(monaco: any) {
    monaco.editor.defineTheme('x-code-default', xcodeDefault);
  }

  return (
    <Editor
      height={height}
      width={width}
      value={value}
      language={language}
      onChange={onChange}
      beforeMount={editorWillMount}
      theme="x-code-default"
      options={{
    		// Read-only
        readOnly,
    		// Control line number display
        lineNumbers,
    		// Control selected line rendering
        renderLineHighlight,
    		// Toggle code folding
        folding,
        smoothScrolling: true,
    		// Editor font size
        fontSize: 13,
    		// Allow scrolling past the last line
        scrollBeyondLastLine: false,
    		// Left gutter width
        lineDecorationsWidth: 19,
    		// Scrollbar styles
        scrollbar: {
          verticalScrollbarSize: 5,
          horizontalScrollbarSize: 5,
        },
    		// Minimap configuration
        minimap: {
          enabled: false,
        },
      }}
    />
  );
};

export default QuietEditor;

