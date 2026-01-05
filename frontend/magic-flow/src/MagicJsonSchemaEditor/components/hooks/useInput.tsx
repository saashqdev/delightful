import { useCallback, useRef } from 'react';

export default function useInput() {
  const isEntering = useRef(false);

  const handleEnterChinese = useCallback((evt: any, newValue: string) => {
    const { data } = evt.nativeEvent
    console.log(evt, data);
    // Chinese IME input starts
    if (evt.type === 'compositionstart') {
      isEntering.current = true;
      return -1;
    }

    // Chinese IME input ends
    if (evt.type === 'compositionend') {
      isEntering.current = false;
    }

    // Skip processing while IME composition is still active
    if (isEntering.current) return -1;

    return newValue;
  }, []);
  return {
    handleEnterChinese,
  };
}
