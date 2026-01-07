# DelightfulRadioFavor Magic Favorite Radio Component

DelightfulRadioFavor is a custom-styled radio button component designed for favorites and preference settings. It presents a set of options for users to choose from, displaying the currently selected option with a distinctive style.

## Properties

| Property      | Type                               | Default | Description                                      |
| ------------- | ---------------------------------- | ------- | ------------------------------------------------ |
| options       | { label: string; value: string }[] | []      | List of options; each has a label and a value    |
| onChange      | (value: string) => void            | -       | Callback when the selected option changes        |
| selectedValue | string                             | -       | Currently selected value                         |
| value         | string                             | -       | Currently selected value (same as `selectedValue`) |

## Basic Usage

```tsx
import DelightfulRadioFavor from '@/components/base/DelightfulRadioFavor';

// Basic usage
const options = [
  { label: 'Option One', value: 'option1' },
  { label: 'Option Two', value: 'option2' },
  { label: 'Option Three', value: 'option3' },
];

const [selected, setSelected] = useState('option1');

<DelightfulRadioFavor
  options={options}
  selectedValue={selected}
  onChange={(value) => setSelected(value)}
/>

// Using the `value` property
<DelightfulRadioFavor
  options={options}
  value={selected}
  onChange={(value) => setSelected(value)}
/>
```

## Features

-   **Custom styles**: More modern appearance than traditional radio buttons
-   **Easy to use**: Simple API for straightforward usage
-   **Flexible configuration**: Customizable list of options
-   **State management**: Maintains internal selection; can also be controlled externally
-   **Responsive design**: Adapts to containers of different sizes

## Use Cases

-   Category selection within favorites
-   Option selection in user preference settings
-   Single-choice filter conditions
-   Any interaction where the user must choose one option from many

The DelightfulRadioFavor component offers a visually appealing way to present single-choice selections, especially suited for interfaces that emphasize user choice such as favorites and preference settings.
