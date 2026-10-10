import { Link, LinkProps } from "react-router-dom"

type BitraceLinkProps = Omit<LinkProps, "to"> & {
  to: string
}

const BitraceLink = ({ to, ...props }: BitraceLinkProps) => {
  return <Link to={to} {...props} />
}

export default BitraceLink
