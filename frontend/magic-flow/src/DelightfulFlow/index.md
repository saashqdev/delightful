# Flow Component

## Basic Usage


```jsx
import { BaseFlow } from '@/MagicFlow/examples';
import React,{ useState, useCallback } from "react"


export default () => {
    return <BaseFlow />
}
```

<!-- ### Custom Params


```jsx
import { SecondFlow } from '@/MagicFlow/examples';
import React,{ useState, useCallback } from "react"


export default () => {
    return <SecondFlow />
}
``` -->

## Open in Modal


```jsx
import { BaseFlowModal } from '@/MagicFlow/examples';
import React,{ useState, useCallback } from "react";
import { Button } from "antd";


export default () => {

    const [open, setOpen] = useState(false)

    return <>
        <Button onClick={() => setOpen(true)}>Open</Button>
        <BaseFlowModal open={open} onClose={() => setOpen(false)}/>
    </>
}
```


