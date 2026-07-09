import type { ComponentPropsWithoutRef, ReactNode } from "react";
import "./Form.css";

type FormPanelProps = ComponentPropsWithoutRef<"form"> & {
  title: string;
  description?: string;
  actions?: ReactNode;
};

export function FormPanel({
  title,
  description,
  actions,
  children,
  className = "",
  ...props
}: FormPanelProps) {
  return (
    <form className={`form-panel ${className}`.trim()} {...props}>
      <div className="form-panel__header">
        <div>
          <h2 className="form-panel__title">{title}</h2>
          {description && <p className="form-panel__description">{description}</p>}
        </div>
        {actions && <div className="form-panel__actions">{actions}</div>}
      </div>

      <div className="form-panel__body">{children}</div>
    </form>
  );
}

type TextInputProps = ComponentPropsWithoutRef<"input"> & {
  label: string;
  helperText?: string;
};

export function TextInput({ label, helperText, id, className = "", ...props }: TextInputProps) {
  const inputId = id ?? props.name;

  return (
    <label className="form-field" htmlFor={inputId}>
      <span className="form-field__label">{label}</span>
      <input
        id={inputId}
        className={`form-field__input ${className}`.trim()}
        {...props}
      />
      {helperText && <span className="form-field__helper">{helperText}</span>}
    </label>
  );
}

type SelectInputProps = ComponentPropsWithoutRef<"select"> & {
  label: string;
  helperText?: string;
  placeholderOption?: string;
  options: Array<{ value: string | number; label: string }>;
};

export function SelectInput({
  label,
  helperText,
  placeholderOption,
  options,
  id,
  className = "",
  ...props
}: SelectInputProps) {
  const inputId = id ?? props.name;

  return (
    <label className="form-field" htmlFor={inputId}>
      <span className="form-field__label">{label}</span>
      <select
        id={inputId}
        className={`form-field__input form-field__select ${className}`.trim()}
        {...props}
      >
        {placeholderOption && (
          <option value="" disabled>
            {placeholderOption}
          </option>
        )}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {helperText && <span className="form-field__helper">{helperText}</span>}
    </label>
  );
}

type SubmitButtonProps = ComponentPropsWithoutRef<"button"> & {
  isLoading?: boolean;
  loadingLabel?: string;
};

export function SubmitButton({
  isLoading = false,
  loadingLabel = "En cours...",
  children,
  disabled,
  className = "",
  ...props
}: SubmitButtonProps) {
  return (
    <button
      type="submit"
      className={`form-submit ${className}`.trim()}
      disabled={disabled || isLoading}
      {...props}
    >
      {isLoading ? loadingLabel : children}
    </button>
  );
}
