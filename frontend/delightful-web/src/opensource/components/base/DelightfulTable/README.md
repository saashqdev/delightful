# DelightfulTable Delightful Table Component

`DelightfulTable` is an enhanced table component based on Ant Design's Table. It offers improved loading states, scrolling behavior, and style optimizations.

## Properties

| Property      | Type                                                   | Default               | Description                               |
| ------------- | ------------------------------------------------------ | --------------------- | ----------------------------------------- |
| loading       | boolean \| SpinProps                                   | false                 | Table loading state                        |
| scroll        | { x?: number \| string \| true; y?: number \| string } | { x: 'max-content' }  | Table scroll configuration                 |
| ...TableProps | -                                                      | -                     | Supports all Ant Design Table properties   |

## Basic Usage

```tsx
import { DelightfulTable } from '@/components/base/DelightfulTable';
import type { ColumnsType } from 'antd/es/table';

// Define data type
interface DataType {
  key: string;
  name: string;
  age: number;
  address: string;
}

// Define columns
const columns: ColumnsType<DataType> = [
  {
    title: 'Name',
    dataIndex: 'name',
    key: 'name',
  },
  {
    title: 'Age',
    dataIndex: 'age',
    key: 'age',
  },
  {
    title: 'Address',
    dataIndex: 'address',
    key: 'address',
  },
];

// Define data
const data: DataType[] = [
  {
    key: '1',
    name: 'Marge Simpson',
    age: 32,
    address: 'Fort Erie, Ontario',
  },
  {
    key: '2',
    name: 'Homer Simpson',
    age: 42,
    address: 'Fort Erie, Ontario',
  },
];

// Basic usage
<DelightfulTable columns={columns} dataSource={data} />

// Loading state
<DelightfulTable loading columns={columns} dataSource={data} />

// Fixed-height scrolling
<DelightfulTable
  scroll={{ y: 300 }}
  columns={columns}
  dataSource={data}
/>

// Row click event
<DelightfulTable
  columns={columns}
  dataSource={data}
  onRow={(record) => ({
    onClick: () => console.log('Clicked row:', record),
  })}
/>
```

## Features

1. **Optimized loading state**: Uses the DelightfulSpin component for a more polished loading experience
2. **Automatic scrolling**: Sets horizontal scroll (`x`) to 'max-content' by default to prevent content squashing
3. **Clickable row styling**: Adds pointer cursor to rows to signal clickability
4. **Empty state optimization**: Hides empty state while loading to avoid flicker
5. **Flexible height control**: Control fixed table height via `scroll.y`

## When to Use

-   When presenting large amounts of structured data
-   When sorting, filtering, or pagination are needed
-   When a better loading state presentation is desirable
-   When table content should be scrollable
-   When rows should be clickable

The DelightfulTable component makes table presentation more attractive and user-friendly while retaining all the powerful features of Ant Design Table.
