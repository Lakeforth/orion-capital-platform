import TransactionHistory from "@/components/transaction-history";
import useUserStore from "@/store/userStore";

export default function Test() {
  const user = useUserStore((state) => state.user);
  console.log("User data:", user);

  return (
    <div>
      <h1>User Data</h1>
      {user ? (
        <div>
          <p>ID: {user.id}</p>
          <p>First Name: {user.first_name}</p>
          <p>Last Name: {user.last_name}</p>
          <p>Email: {user.email}</p>
          <p>Phone: {user.phone}</p>
          <p>Country Code: {user.country_code}</p>
          <p>Country: {user.country}</p>
          <p>Username: {user.username}</p>
          <p>
            Avatar: <img src={user.avatar} alt="User Avatar" width="50" />
          </p>
          <p>Balance: {user.balance}</p>
          <p>Copy Trader: {user.copy_trader}</p>
        </div>
      ) : (
        <p>No user logged in</p>
      )}

      <TransactionHistory />
    </div>
  );
}
